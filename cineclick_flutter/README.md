# CinéClick — Flutter OTT Streaming App

A full-featured OTT (Over-The-Top) streaming mobile application built with Flutter and Firebase. Stream movies, manage subscriptions, upload content, and administer users — all with a sleek dark-themed UI.

---

## 1. Project Overview

CinéClick is a role-based streaming platform with three user types:

| Role     | Capabilities |
|----------|-------------|
| **User** | Browse & search approved movies, watch (if subscribed), manage favourites |
| **Uploader** | Everything a User can do + upload movies for admin approval |
| **Admin** | Full access — approve/reject movies, manage user roles, watch without subscription |

### Key Features
- Firebase Authentication (email/password)
- Firestore for user profiles, movie catalogue, subscriptions, watch history, and favourites
- Firebase Storage for video and thumbnail uploads
- Chewie-powered full-screen video player
- Mock subscription checkout with 3 tiered plans
- Dark streaming-style theme with red/orange accents

---

## 2. Firebase Setup

### Step 1 — Create a Firebase Project
1. Go to [Firebase Console](https://console.firebase.google.com/).
2. Click **Add project** → enter `CineClick` → follow the wizard.

### Step 2 — Enable Services
| Service | Steps |
|---------|-------|
| **Authentication** | Build → Authentication → Get started → Email/Password → Enable → Save |
| **Firestore** | Build → Firestore Database → Create database → Start in **production mode** → choose region |
| **Storage** | Build → Storage → Get started → Start in **production mode** → choose region |

### Step 3 — Register Your Android App
1. In Project Overview click **Add app** → Android.
2. Enter package name: `com.example.cineclick_flutter` (or your own).
3. Download **`google-services.json`** and place it at:
   ```
   cineclick_flutter/android/app/google-services.json
   ```
4. Follow the SDK setup instructions shown in the console.

### Step 4 — Register Your iOS App (optional)
1. Click **Add app** → iOS.
2. Enter bundle ID, download **`GoogleService-Info.plist`**, place it at:
   ```
   cineclick_flutter/ios/Runner/GoogleService-Info.plist
   ```

### Step 5 — Android Gradle Configuration

`android/build.gradle` — add to `dependencies`:
```gradle
classpath 'com.google.gms:google-services:4.4.1'
```

`android/app/build.gradle` — add at the very bottom:
```gradle
apply plugin: 'com.google.gms.google-services'
```

Also ensure `minSdkVersion` is at least **21** in `android/app/build.gradle`.

---

## 3. How to Run

```bash
# Install dependencies
flutter pub get

# Run on connected device / emulator
flutter run

# Build release APK
flutter build apk --release
```

> **Note:** A valid `google-services.json` (Android) or `GoogleService-Info.plist` (iOS) must be present before the app can connect to Firebase.

---

## 4. Firestore Collection Schema

### `users/{uid}`
```
name            : String
email           : String
role            : String  ("user" | "uploader" | "admin")
subscriptionStatus : Boolean
```

### `movies/{movieId}`
```
title           : String
description     : String
thumbnailUrl    : String
videoUrl        : String
uploaderId      : String  (uid of uploader)
isApproved      : Boolean
createdAt       : Timestamp
```

### `subscriptions/{uid}`
```
userId          : String
plan            : String  ("Basic" | "Standard" | "Premium")
expiryDate      : Timestamp
```

### `users/{uid}/history/{movieId}`
```
movieId         : String
watchedAt       : Timestamp
```

### `users/{uid}/favorites/{movieId}`
```
movieId         : String
addedAt         : Timestamp
```

---

## 5. Deploy Firestore Security Rules

The security rules are in `firebase.rules`. To deploy:

```bash
# Install Firebase CLI (if not already installed)
npm install -g firebase-tools

# Login
firebase login

# Initialise Firebase in the project (select Firestore)
firebase init firestore

# Copy the rules file
cp firebase.rules firestore.rules

# Deploy only Firestore rules
firebase deploy --only firestore:rules
```

Alternatively, paste the contents of `firebase.rules` directly into the Firebase Console under **Firestore → Rules**.

---

## 6. Seed an Admin User

After registering your first account through the app, manually promote it to admin in Firestore:

1. Open Firebase Console → Firestore → `users` collection.
2. Find your document and change `role` from `"user"` to `"admin"`.

The app will reflect the new role on next sign-in.

---

## 7. Project Structure

```
lib/
├── main.dart                    # App entry point, theme, routes
├── models/
│   ├── user_model.dart
│   ├── movie_model.dart
│   └── subscription_model.dart
├── services/
│   ├── auth_service.dart
│   ├── firestore_service.dart
│   └── storage_service.dart
├── providers/
│   ├── auth_provider.dart
│   └── movie_provider.dart
├── screens/
│   ├── splash_screen.dart
│   ├── login_screen.dart
│   ├── register_screen.dart
│   ├── home_screen.dart
│   ├── movie_detail_screen.dart
│   ├── video_player_screen.dart
│   ├── upload_screen.dart
│   ├── admin_dashboard_screen.dart
│   └── subscription_screen.dart
└── widgets/
    ├── movie_card.dart
    └── loading_widget.dart
```
