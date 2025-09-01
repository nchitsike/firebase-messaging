# Firebase Messaging PHP Library

A PHP library for authenticating with Google OAuth2 and sending notifications via Firebase Cloud Messaging (FCM).

## Installation

Install via Composer:

```bash
composer require nchitsike/firebase-messaging


Generating a Firebase Service Account JSON FileTo use this library, you need a Firebase service account JSON file. Follow these steps to generate it in the Firebase Console:Go to the Firebase Console:Navigate to console.firebase.google.com.

1. Sign in with your Google account and select or create a Firebase project.

2. Access Project Settings:In the Firebase Console, click the gear icon in the top-left corner and select Project settings.

3. Navigate to Service Accounts:In the Project settings page, go to the Service accounts tab.

4. Generate a New Private Key:Under Firebase Admin SDK, click Generate new private key.
A confirmation dialog will appear. Click Generate key to download the JSON file.
5. Save the file securely (e.g., your-service-account.json). Do not commit this file to version control.

6. Secure the JSON File:Store the JSON file in a secure location accessible to your application.
Add the file path to your .gitignore to prevent accidental exposure.

