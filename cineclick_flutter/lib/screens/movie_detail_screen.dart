import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:provider/provider.dart';
import '../models/movie_model.dart';
import '../providers/auth_provider.dart';
import '../services/firestore_service.dart';
import '../widgets/loading_widget.dart';

class MovieDetailScreen extends StatefulWidget {
  const MovieDetailScreen({super.key});

  @override
  State<MovieDetailScreen> createState() => _MovieDetailScreenState();
}

class _MovieDetailScreenState extends State<MovieDetailScreen> {
  final FirestoreService _firestoreService = FirestoreService();
  bool _isFavorite = false;
  bool _favoriteLoading = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final movie = ModalRoute.of(context)!.settings.arguments as MovieModel;
    final auth = context.read<AppAuthProvider>();
    if (auth.user != null) {
      _loadFavoriteStatus(auth.user!.id, movie.id);
      _firestoreService.addToWatchHistory(auth.user!.id, movie.id);
    }
  }

  Future<void> _loadFavoriteStatus(String userId, String movieId) async {
    final favs = await _firestoreService.getFavorites(userId);
    if (mounted) {
      setState(() => _isFavorite = favs.contains(movieId));
    }
  }

  Future<void> _toggleFavorite(String userId, String movieId) async {
    setState(() => _favoriteLoading = true);
    try {
      await _firestoreService.toggleFavorite(userId, movieId);
      setState(() => _isFavorite = !_isFavorite);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _favoriteLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final movie = ModalRoute.of(context)!.settings.arguments as MovieModel;
    final auth = context.watch<AppAuthProvider>();
    final canWatch = auth.isSubscribed;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                movie.title,
                style: const TextStyle(
                  shadows: [Shadow(blurRadius: 8, color: Colors.black)],
                ),
              ),
              background: movie.thumbnailUrl.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: movie.thumbnailUrl,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => const LoadingWidget(),
                      errorWidget: (_, __, ___) => Container(
                        color: Colors.grey[900],
                        child: const Icon(Icons.movie, size: 60, color: Colors.white24),
                      ),
                    )
                  : Container(
                      color: Colors.grey[900],
                      child: const Icon(Icons.movie, size: 60, color: Colors.white24),
                    ),
            ),
            actions: [
              if (auth.user != null)
                _favoriteLoading
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : IconButton(
                        icon: Icon(
                          _isFavorite ? Icons.favorite : Icons.favorite_border,
                          color: _isFavorite ? Colors.red : null,
                        ),
                        onPressed: () =>
                            _toggleFavorite(auth.user!.id, movie.id),
                      ),
            ],
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    movie.title,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    movie.description,
                    style: const TextStyle(
                      fontSize: 15,
                      color: Colors.white70,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 28),
                  if (canWatch)
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        icon: const Icon(Icons.play_arrow),
                        label: const Text('Watch Now'),
                        onPressed: () => Navigator.pushNamed(
                          context,
                          '/player',
                          arguments: movie.videoUrl,
                        ),
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          textStyle: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                      ),
                    )
                  else
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.lock_outline),
                        label: const Text('Subscribe to Watch'),
                        onPressed: () =>
                            Navigator.pushNamed(context, '/subscribe'),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
