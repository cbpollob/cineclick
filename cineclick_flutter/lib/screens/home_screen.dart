import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../providers/movie_provider.dart';
import '../widgets/movie_card.dart';
import '../widgets/loading_widget.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _showSearch = false;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MovieProvider>().listenToApprovedMovies();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _onNavTap(int index, AppAuthProvider auth) {
    if (index == 0) return; // already on Home
    if (auth.isAdmin) {
      if (index == 1) Navigator.pushNamed(context, '/upload');
      if (index == 2) Navigator.pushNamed(context, '/admin');
    } else if (auth.isUploader) {
      if (index == 1) Navigator.pushNamed(context, '/upload');
    } else {
      // Regular user — index 1 is Subscribe
      if (index == 1) Navigator.pushNamed(context, '/subscribe');
    }
  }

  List<BottomNavigationBarItem> _buildNavItems(AppAuthProvider auth) {
    final items = <BottomNavigationBarItem>[
      const BottomNavigationBarItem(
        icon: Icon(Icons.home_outlined),
        activeIcon: Icon(Icons.home),
        label: 'Home',
      ),
    ];
    if (auth.isUploader) {
      items.add(const BottomNavigationBarItem(
        icon: Icon(Icons.upload_outlined),
        activeIcon: Icon(Icons.upload),
        label: 'Upload',
      ));
    }
    if (auth.isAdmin) {
      items.add(const BottomNavigationBarItem(
        icon: Icon(Icons.admin_panel_settings_outlined),
        activeIcon: Icon(Icons.admin_panel_settings),
        label: 'Admin',
      ));
    }
    if (!auth.isUploader && !auth.isAdmin) {
      // Regular user always gets a second tab so BottomNavigationBar has >= 2 items
      items.add(const BottomNavigationBarItem(
        icon: Icon(Icons.star_border),
        activeIcon: Icon(Icons.star),
        label: 'Subscribe',
      ));
    }
    return items;
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AppAuthProvider>();
    final movieProvider = context.watch<MovieProvider>();

    return Scaffold(
      appBar: AppBar(
        title: _showSearch
            ? TextField(
                controller: _searchController,
                autofocus: true,
                decoration: const InputDecoration(
                  hintText: 'Search movies...',
                  border: InputBorder.none,
                  hintStyle: TextStyle(color: Colors.white54),
                ),
                style: const TextStyle(color: Colors.white),
                onChanged: movieProvider.setSearchQuery,
              )
            : const Text(
                'CinéClick',
                style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1),
              ),
        actions: [
          IconButton(
            icon: Icon(_showSearch ? Icons.close : Icons.search),
            onPressed: () {
              setState(() => _showSearch = !_showSearch);
              if (!_showSearch) {
                _searchController.clear();
                movieProvider.clearSearch();
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () async {
              await auth.signOut();
              if (context.mounted) {
                Navigator.pushReplacementNamed(context, '/login');
              }
            },
          ),
        ],
      ),
      body: Column(
        children: [
          if (!auth.isSubscribed)
            _SubscribeBanner(
              onSubscribe: () => Navigator.pushNamed(context, '/subscribe'),
            ),
          Expanded(
            child: movieProvider.isLoading
                ? const LoadingWidget()
                : movieProvider.filteredMovies.isEmpty
                    ? const _EmptyState()
                    : GridView.builder(
                        padding: const EdgeInsets.all(12),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          childAspectRatio: 0.68,
                          crossAxisSpacing: 10,
                          mainAxisSpacing: 10,
                        ),
                        itemCount: movieProvider.filteredMovies.length,
                        itemBuilder: (_, i) => MovieCard(
                          movie: movieProvider.filteredMovies[i],
                        ),
                      ),
          ),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 0,
        onTap: (i) => _onNavTap(i, auth),
        items: _buildNavItems(auth),
      ),
    );
  }
}

class _SubscribeBanner extends StatelessWidget {
  final VoidCallback onSubscribe;
  const _SubscribeBanner({required this.onSubscribe});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.15),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          const Icon(Icons.star_outline, size: 18, color: Colors.amber),
          const SizedBox(width: 8),
          const Expanded(
            child: Text(
              'Subscribe to watch all movies',
              style: TextStyle(fontSize: 13),
            ),
          ),
          TextButton(
            onPressed: onSubscribe,
            child: const Text('Subscribe'),
          ),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.movie_filter_outlined,
              size: 64, color: Colors.white24),
          const SizedBox(height: 16),
          const Text('No movies found', style: TextStyle(color: Colors.white38)),
        ],
      ),
    );
  }
}
