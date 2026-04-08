import 'dart:async';
import 'package:flutter/foundation.dart';
import '../models/movie_model.dart';
import '../services/firestore_service.dart';

class MovieProvider extends ChangeNotifier {
  final FirestoreService _firestoreService = FirestoreService();

  List<MovieModel> _movies = [];
  String _searchQuery = '';
  bool _isLoading = false;
  String? _errorMessage;
  StreamSubscription<List<MovieModel>>? _moviesSubscription;

  List<MovieModel> get movies => _movies;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  String get searchQuery => _searchQuery;

  List<MovieModel> get filteredMovies {
    if (_searchQuery.isEmpty) return _movies;
    final query = _searchQuery.toLowerCase();
    return _movies
        .where((m) => m.title.toLowerCase().contains(query))
        .toList();
  }

  void listenToApprovedMovies() {
    _moviesSubscription?.cancel();
    _setLoading(true);
    _moviesSubscription =
        _firestoreService.getApprovedMovies().listen(
      (movies) {
        _movies = movies;
        _setLoading(false);
        notifyListeners();
      },
      onError: (e) {
        _setError(e.toString());
        _setLoading(false);
      },
    );
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    notifyListeners();
  }

  void clearSearch() {
    _searchQuery = '';
    notifyListeners();
  }

  @override
  void dispose() {
    _moviesSubscription?.cancel();
    super.dispose();
  }

  void _setLoading(bool value) {
    _isLoading = value;
    notifyListeners();
  }

  void _setError(String message) {
    _errorMessage = message;
    notifyListeners();
  }
}
