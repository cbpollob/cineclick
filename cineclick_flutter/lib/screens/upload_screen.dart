import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'package:provider/provider.dart';
import '../models/movie_model.dart';
import '../providers/auth_provider.dart';
import '../services/firestore_service.dart';
import '../services/storage_service.dart';
import '../widgets/loading_widget.dart';
import '../widgets/movie_card.dart';

class UploadScreen extends StatefulWidget {
  const UploadScreen({super.key});

  @override
  State<UploadScreen> createState() => _UploadScreenState();
}

class _UploadScreenState extends State<UploadScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descController = TextEditingController();
  final FirestoreService _firestoreService = FirestoreService();
  final StorageService _storageService = StorageService();

  File? _thumbnailFile;
  File? _videoFile;
  String? _thumbnailName;
  String? _videoName;
  bool _isUploading = false;
  double _uploadProgress = 0;

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    super.dispose();
  }

  Future<void> _pickThumbnail() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );
    if (picked != null) {
      setState(() {
        _thumbnailFile = File(picked.path);
        _thumbnailName = picked.name;
      });
    }
  }

  Future<void> _pickVideo() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.video,
      allowMultiple: false,
    );
    if (result != null && result.files.single.path != null) {
      setState(() {
        _videoFile = File(result.files.single.path!);
        _videoName = result.files.single.name;
      });
    }
  }

  Future<void> _upload() async {
    if (!_formKey.currentState!.validate()) return;
    if (_thumbnailFile == null) {
      _showSnack('Please select a thumbnail image.');
      return;
    }
    if (_videoFile == null) {
      _showSnack('Please select a video file.');
      return;
    }

    final auth = context.read<AppAuthProvider>();
    final uid = auth.user!.id;
    setState(() {
      _isUploading = true;
      _uploadProgress = 0;
    });

    try {
      final timestamp = DateTime.now().millisecondsSinceEpoch;

      setState(() => _uploadProgress = 0.1);
      final thumbnailUrl = await _storageService.uploadThumbnail(
        _thumbnailFile!,
        '${uid}_${timestamp}_$_thumbnailName',
      );

      setState(() => _uploadProgress = 0.5);
      final videoUrl = await _storageService.uploadVideo(
        _videoFile!,
        '${uid}_${timestamp}_$_videoName',
      );

      setState(() => _uploadProgress = 0.9);
      final movie = MovieModel(
        id: '',
        title: _titleController.text.trim(),
        description: _descController.text.trim(),
        thumbnailUrl: thumbnailUrl,
        videoUrl: videoUrl,
        uploaderId: uid,
        isApproved: false,
        createdAt: DateTime.now(),
      );
      await _firestoreService.addMovie(movie);

      if (mounted) {
        setState(() {
          _uploadProgress = 1.0;
          _isUploading = false;
          _thumbnailFile = null;
          _videoFile = null;
          _thumbnailName = null;
          _videoName = null;
        });
        _titleController.clear();
        _descController.clear();
        _showSnack('Movie uploaded! Awaiting admin approval.');
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isUploading = false);
        _showSnack('Upload failed: $e');
      }
    }
  }

  void _showSnack(String msg) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AppAuthProvider>();
    if (auth.user == null) return const SizedBox.shrink();

    return Scaffold(
      appBar: AppBar(title: const Text('Upload Movie')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Form(
              key: _formKey,
              child: Column(
                children: [
                  TextFormField(
                    controller: _titleController,
                    decoration: const InputDecoration(
                      labelText: 'Movie Title',
                      prefixIcon: Icon(Icons.title),
                    ),
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Enter a title' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _descController,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      labelText: 'Description',
                      prefixIcon: Icon(Icons.description_outlined),
                      alignLabelWithHint: true,
                    ),
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Enter a description' : null,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            _FilePicker(
              label: 'Thumbnail Image',
              icon: Icons.image_outlined,
              fileName: _thumbnailName,
              onPick: _pickThumbnail,
            ),
            const SizedBox(height: 12),
            _FilePicker(
              label: 'Video File',
              icon: Icons.videocam_outlined,
              fileName: _videoName,
              onPick: _pickVideo,
            ),
            const SizedBox(height: 28),
            if (_isUploading) ...[
              LinearProgressIndicator(value: _uploadProgress),
              const SizedBox(height: 12),
              const Center(child: Text('Uploading, please wait...')),
            ] else
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  icon: const Icon(Icons.cloud_upload_outlined),
                  label: const Text('Upload Movie'),
                  onPressed: _upload,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                ),
              ),
            const SizedBox(height: 32),
            Text(
              'Your Uploads',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            StreamBuilder<List<MovieModel>>(
              stream:
                  _firestoreService.getUploaderMovies(auth.user!.id),
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const LoadingWidget(size: 30);
                }
                if (!snapshot.hasData || snapshot.data!.isEmpty) {
                  return const Text(
                    'No uploads yet.',
                    style: TextStyle(color: Colors.white54),
                  );
                }
                return GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate:
                      const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.68,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: snapshot.data!.length,
                  itemBuilder: (_, i) =>
                      MovieCard(movie: snapshot.data![i]),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _FilePicker extends StatelessWidget {
  final String label;
  final IconData icon;
  final String? fileName;
  final VoidCallback onPick;

  const _FilePicker({
    required this.label,
    required this.icon,
    required this.fileName,
    required this.onPick,
  });

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      icon: Icon(fileName != null ? Icons.check_circle_outline : icon),
      label: Text(
        fileName != null ? fileName! : 'Select $label',
        overflow: TextOverflow.ellipsis,
      ),
      onPressed: onPick,
      style: OutlinedButton.styleFrom(
        foregroundColor:
            fileName != null ? Colors.greenAccent : null,
        side: BorderSide(
          color: fileName != null ? Colors.greenAccent : Colors.white30,
        ),
        minimumSize: const Size(double.infinity, 48),
        alignment: Alignment.centerLeft,
      ),
    );
  }
}
