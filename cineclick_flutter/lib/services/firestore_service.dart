import 'package:cloud_firestore/cloud_firestore.dart';
import '../models/movie_model.dart';
import '../models/user_model.dart';
import '../models/subscription_model.dart';

class FirestoreService {
  final FirebaseFirestore _db = FirebaseFirestore.instance;

  // ── Movies ──────────────────────────────────────────────────────────────

  Stream<List<MovieModel>> getApprovedMovies() {
    return _db
        .collection('movies')
        .where('isApproved', isEqualTo: true)
        .orderBy('createdAt', descending: true)
        .snapshots()
        .map(_moviesFromSnapshot);
  }

  Stream<List<MovieModel>> getAllMovies() {
    return _db
        .collection('movies')
        .orderBy('createdAt', descending: true)
        .snapshots()
        .map(_moviesFromSnapshot);
  }

  Stream<List<MovieModel>> getUploaderMovies(String uploaderId) {
    return _db
        .collection('movies')
        .where('uploaderId', isEqualTo: uploaderId)
        .orderBy('createdAt', descending: true)
        .snapshots()
        .map(_moviesFromSnapshot);
  }

  Future<void> addMovie(MovieModel movie) async {
    await _db.collection('movies').add(movie.toMap());
  }

  Future<void> approveMovie(String movieId, bool approved) async {
    await _db
        .collection('movies')
        .doc(movieId)
        .update({'isApproved': approved});
  }

  List<MovieModel> _moviesFromSnapshot(QuerySnapshot snapshot) {
    return snapshot.docs
        .map((doc) =>
            MovieModel.fromMap(doc.data() as Map<String, dynamic>, doc.id))
        .toList();
  }

  // ── Users ───────────────────────────────────────────────────────────────

  Future<UserModel?> getUser(String userId) async {
    final doc = await _db.collection('users').doc(userId).get();
    if (!doc.exists) return null;
    return UserModel.fromMap(doc.data()!, doc.id);
  }

  Future<void> updateUserRole(String userId, UserRole role) async {
    await _db.collection('users').doc(userId).update({'role': role.name});
  }

  Stream<List<UserModel>> getAllUsers() {
    return _db.collection('users').snapshots().map((snapshot) {
      return snapshot.docs
          .map((doc) =>
              UserModel.fromMap(doc.data(), doc.id))
          .toList();
    });
  }

  Future<void> updateSubscriptionStatus(String userId, bool status) async {
    await _db
        .collection('users')
        .doc(userId)
        .update({'subscriptionStatus': status});
  }

  // ── Subscriptions ────────────────────────────────────────────────────────

  Future<void> addSubscription(SubscriptionModel subscription) async {
    await _db
        .collection('subscriptions')
        .doc(subscription.userId)
        .set(subscription.toMap());
    await updateSubscriptionStatus(subscription.userId, true);
  }

  Future<SubscriptionModel?> getSubscription(String userId) async {
    final doc = await _db.collection('subscriptions').doc(userId).get();
    if (!doc.exists) return null;
    return SubscriptionModel.fromMap(doc.data()!, doc.id);
  }

  // ── Watch History ────────────────────────────────────────────────────────

  Future<void> addToWatchHistory(String userId, String movieId) async {
    await _db.collection('users').doc(userId).collection('history').doc(movieId).set({
      'movieId': movieId,
      'watchedAt': FieldValue.serverTimestamp(),
    });
  }

  Future<List<String>> getWatchHistory(String userId) async {
    final snapshot = await _db
        .collection('users')
        .doc(userId)
        .collection('history')
        .orderBy('watchedAt', descending: true)
        .get();
    return snapshot.docs.map((doc) => doc.id).toList();
  }

  // ── Favourites ───────────────────────────────────────────────────────────

  Future<void> toggleFavorite(String userId, String movieId) async {
    final ref = _db
        .collection('users')
        .doc(userId)
        .collection('favorites')
        .doc(movieId);
    final doc = await ref.get();
    if (doc.exists) {
      await ref.delete();
    } else {
      await ref.set({'movieId': movieId, 'addedAt': FieldValue.serverTimestamp()});
    }
  }

  Future<List<String>> getFavorites(String userId) async {
    final snapshot = await _db
        .collection('users')
        .doc(userId)
        .collection('favorites')
        .get();
    return snapshot.docs.map((doc) => doc.id).toList();
  }

  Stream<bool> isFavoriteStream(String userId, String movieId) {
    return _db
        .collection('users')
        .doc(userId)
        .collection('favorites')
        .doc(movieId)
        .snapshots()
        .map((doc) => doc.exists);
  }
}
