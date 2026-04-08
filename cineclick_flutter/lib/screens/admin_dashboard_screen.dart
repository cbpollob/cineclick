import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/user_model.dart';
import '../models/movie_model.dart';
import '../providers/auth_provider.dart';
import '../services/firestore_service.dart';
import '../widgets/loading_widget.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final FirestoreService _firestoreService = FirestoreService();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AppAuthProvider>();
    if (!auth.isAdmin) {
      return const Scaffold(
        body: Center(child: Text('Access denied.')),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Admin Dashboard'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.people_outline), text: 'Users'),
            Tab(icon: Icon(Icons.movie_outlined), text: 'Movies'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _UsersTab(firestoreService: _firestoreService),
          _MoviesTab(firestoreService: _firestoreService),
        ],
      ),
    );
  }
}

// ── Users Tab ─────────────────────────────────────────────────────────────────

class _UsersTab extends StatelessWidget {
  final FirestoreService firestoreService;
  const _UsersTab({required this.firestoreService});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<List<UserModel>>(
      stream: firestoreService.getAllUsers(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const LoadingWidget();
        }
        if (!snapshot.hasData || snapshot.data!.isEmpty) {
          return const Center(child: Text('No users found.'));
        }
        final users = snapshot.data!;
        return ListView.separated(
          padding: const EdgeInsets.symmetric(vertical: 8),
          itemCount: users.length,
          separatorBuilder: (_, __) => const Divider(height: 1),
          itemBuilder: (_, i) => _UserTile(
            user: users[i],
            firestoreService: firestoreService,
          ),
        );
      },
    );
  }
}

class _UserTile extends StatelessWidget {
  final UserModel user;
  final FirestoreService firestoreService;
  const _UserTile({required this.user, required this.firestoreService});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        child: Text(
          user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
        ),
      ),
      title: Text(user.name),
      subtitle: Text(user.email),
      trailing: DropdownButton<UserRole>(
        value: user.role,
        underline: const SizedBox.shrink(),
        isDense: true,
        items: UserRole.values
            .map((r) => DropdownMenuItem(
                  value: r,
                  child: Text(
                    r.name[0].toUpperCase() + r.name.substring(1),
                    style: const TextStyle(fontSize: 13),
                  ),
                ))
            .toList(),
        onChanged: (newRole) async {
          if (newRole == null || newRole == user.role) return;
          try {
            await firestoreService.updateUserRole(user.id, newRole);
            if (context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                    content: Text(
                        '${user.name} is now a ${newRole.name}')),
              );
            }
          } catch (e) {
            if (context.mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('Error: $e')),
              );
            }
          }
        },
      ),
    );
  }
}

// ── Movies Tab ────────────────────────────────────────────────────────────────

class _MoviesTab extends StatelessWidget {
  final FirestoreService firestoreService;
  const _MoviesTab({required this.firestoreService});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<List<MovieModel>>(
      stream: firestoreService.getAllMovies(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const LoadingWidget();
        }
        if (!snapshot.hasData || snapshot.data!.isEmpty) {
          return const Center(child: Text('No movies found.'));
        }
        final movies = snapshot.data!;
        return ListView.separated(
          padding: const EdgeInsets.symmetric(vertical: 8),
          itemCount: movies.length,
          separatorBuilder: (_, __) => const Divider(height: 1),
          itemBuilder: (_, i) => _MovieTile(
            movie: movies[i],
            firestoreService: firestoreService,
          ),
        );
      },
    );
  }
}

class _MovieTile extends StatelessWidget {
  final MovieModel movie;
  final FirestoreService firestoreService;
  const _MovieTile({required this.movie, required this.firestoreService});

  Future<void> _setApproval(BuildContext context, bool approved) async {
    try {
      await firestoreService.approveMovie(movie.id, approved);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text(
                  '"${movie.title}" ${approved ? 'approved' : 'rejected'}')),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Container(
        width: 50,
        height: 50,
        decoration: BoxDecoration(
          color: Colors.grey[850],
          borderRadius: BorderRadius.circular(6),
        ),
        child: const Icon(Icons.movie, color: Colors.white38),
      ),
      title: Text(movie.title, maxLines: 1, overflow: TextOverflow.ellipsis),
      subtitle: Text(
        movie.isApproved ? 'Approved' : 'Pending',
        style: TextStyle(
          color: movie.isApproved ? Colors.greenAccent : Colors.orangeAccent,
          fontSize: 12,
        ),
      ),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (!movie.isApproved)
            IconButton(
              icon: const Icon(Icons.check_circle_outline,
                  color: Colors.greenAccent),
              tooltip: 'Approve',
              onPressed: () => _setApproval(context, true),
            ),
          if (movie.isApproved)
            IconButton(
              icon: const Icon(Icons.cancel_outlined,
                  color: Colors.orangeAccent),
              tooltip: 'Revoke',
              onPressed: () => _setApproval(context, false),
            ),
        ],
      ),
    );
  }
}
