import 'dart:io';
import 'package:firebase_storage/firebase_storage.dart';

class StorageService {
  final FirebaseStorage _storage = FirebaseStorage.instance;

  Future<String> uploadVideo(File file, String fileName) async {
    final ref = _storage.ref().child('videos/$fileName');
    final task = await ref.putFile(
      file,
      SettableMetadata(contentType: 'video/mp4'),
    );
    return task.ref.getDownloadURL();
  }

  Future<String> uploadThumbnail(File file, String fileName) async {
    final ref = _storage.ref().child('thumbnails/$fileName');
    final task = await ref.putFile(
      file,
      SettableMetadata(contentType: 'image/jpeg'),
    );
    return task.ref.getDownloadURL();
  }
}
