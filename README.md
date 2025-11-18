# Complete API Documentation

## Table of Contents
- [Test Data Setup](#test-data-setup)
- [Authentication](#authentication)
- [Organizations](#organizations-crud)
- [Shield Module](#shield-module)
- [Podcasts](#podcasts)
- [Releases](#releases)
- [Blogs](#blogs)
- [Models](#models)
- [Subscription](#subscription)
- [Certificate](#certificate)

---

### Public Endpoints (No Auth Required)

#### Get Shield Analytics
**Endpoint:** `GET /api/shield/analytics`

**Test Request:**
```bash
GET /api/shield/analytics
```

**Expected Response:**
```json
{
  "success": true,
  "total_organizations_awarded": 0,
  "highest_rate": 0,
  "average_rate": 0,
  "organizations_completed_ratio": 0
}
```

#### Get Organizations List
**Endpoint:** `GET /api/shield/organizations`

**Test Request:**
```bash
GET /api/shield/organizations?page=1&limit=10
```

**Test Request with Filters:**
```bash
GET /api/shield/organizations?query=tech&grade=excellent&region=North&year=2024
```

**Expected Response:**
```json
{
  "success": true,
  "data": [],
  "pagination": {
    "current_page": 1,
    "total_pages": 0,
    "total_items": 0,
    "per_page": 10
  }
}
```

---

### Protected Endpoints (Auth Required)

#### 1. Get Questions with Saved Answers
**Endpoint:** `GET /api/shield/questions`
**Authentication:** Required

**Test Request:**
```bash
GET /api/shield/questions
Authorization: Bearer {your_token}
```

**Expected Response:**
```json
{
  "success": true,
  "axes": [
    {
      "id": "1",
      "title": "Data Protection",
      "description": "Questions related to data security and privacy",
      "questions": [
        {
          "id": "1",
          "question": "Does your organization have a data protection policy?",
          "has_attachment": false,
          "current_answer": null,
          "attachment": null
        }
      ]
    }
  ]
}
```

---

#### 2. Save Answers (Draft)
**Endpoint:** `POST /api/shield/save`
**Authentication:** Required

**Test Request - Partial Submission:**
```bash
POST /api/shield/save
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "axis_id": 1,
  "questions": [
    {
      "question_id": 1,
      "answer": true
    },
    {
      "question_id": 2,
      "answer": false
    },
    {
      "question_id": 3,
      "answer": true
    },
    {
      "question_id": 4,
      "answer": true
    }
  ],
  "attachments": [
    "https://your-domain.com/storage/uploads/axis1-doc1.pdf",
    "https://your-domain.com/storage/uploads/axis1-doc2.pdf",
    "https://your-domain.com/storage/uploads/axis1-doc3.pdf"
  ]
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Answers saved successfully"
}
```

---

#### 3. Upload Attachment
**Endpoint:** `POST /api/shield/attachment/upload`
**Authentication:** Required

**Test Request (using curl):**
```bash
curl -X POST http://your-domain/api/shield/attachment/upload \
  -H "Authorization: Bearer {your_token}" \
  -F "file=@/path/to/document.pdf"
```

**Test Request (using Postman/Insomnia):**
- Method: POST
- URL: `/api/shield/attachment/upload`
- Headers: `Authorization: Bearer {token}`
- Body: form-data
-Key: files[]   Value: file1.pdf
-Key: files[]   Value: file2.pdf
-Key: files[]   Value: file3.pdf


**Expected Response:**
```json
{
  "success": true,
  "file_url": "http://example.com/storage/shield_attachments/1/document.pdf",
  "file_path": "shield_attachments/1/document.pdf"
}
```

---

#### 4. Submit Final Answers
**Endpoint:** `POST /api/shield/submit`
**Authentication:** Required

**Test Request - Complete Submission:**
```bash
POST /api/shield/submit
Authorization: Bearer {your_token}
Content-Type: application/json
{
  "axis_id": 1,
  "questions": [
    {
      "question_id": 1,
      "answer": true
    },
    {
      "question_id": 2,
      "answer": false
    },
    {
      "question_id": 3,
      "answer": true
    },
    {
      "question_id": 4,
      "answer": true
    }
  ],
  "attachments": [
    "https://your-domain.com/storage/uploads/axis1-doc1.pdf",
    "https://your-domain.com/storage/uploads/axis1-doc2.pdf",
    "https://your-domain.com/storage/uploads/axis1-doc3.pdf"
  ]
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Answers submitted successfully",
  "total_score": 75.00,
  "rank": "silver"
}
```

**Error Response (Missing Questions):**
```json
{
  "success": false,
  "message": "All questions must be answered before submitting",
  "missing_questions": [3, 4]
}
```

---

#### 5. Download Results
**Endpoint:** `GET /api/shield/download-results`
**Authentication:** Required

**Test Request:**
```bash
GET /api/shield/download-results
Authorization: Bearer {your_token}
```

**Response:** PDF file download

---

## Podcasts

### Get All Podcasts
**Endpoint:** `GET /api/podcasts`

**Test Request:**
```bash
GET /api/podcasts
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Tech Talk Episode 1",
      "description": "Discussion about latest tech trends",
      "audio_url": "http://example.com/podcasts/episode1.mp3",
      "duration": "45:30",
      "published_at": "2024-01-15"
    }
  ]
}
```

### Get Single Podcast
**Endpoint:** `GET /api/podcasts/{id}`

**Test Request:**
```bash
GET /api/podcasts/1
```

---

## Releases

### Get All Releases
**Endpoint:** `GET /api/releases`

**Test Request:**
```bash
GET /api/releases
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Version 2.0 Release",
      "version": "2.0.0",
      "release_date": "2024-01-20",
      "description": "Major update with new features",
      "download_url": "http://example.com/releases/v2.0.0.zip"
    }
  ]
}
```

### Get Single Release
**Endpoint:** `GET /api/releases/{id}`

**Test Request:**
```bashs
GET /api/releases/1
```

### Download Release
**Endpoint:** `GET /api/releases/{id}/download`

**Test Request:**
```bash
GET /api/releases/1/download
```

**Response:** File download

---
## Subscription 

This API allows managing users, subscription plans, and retrieving subscription information in a Laravel application.

Base URL
http://your-domain.com/api

Authentication

All endpoints require authenticated users using Laravel's API authentication (sanctum or passport).

Endpoints
1. Subscribation plans

GET /api/plans TO get all existing plans

POST /api/subscription-requests
Authorization: Bearer {token}

POST /api/subscription

{
  name:hassan
email:haasklany@gmail.com
phone:01003232423
plan_id:1
receipt_image : "file"
}


Response:

{
  "message": "You are now subscribed to Pro.",
  "subscription": {
    "id": 5,
    "user_id": 2,
    "plan_id": 1,
    "starts_at": "2025-11-17",
    "ends_at": "2025-12-17",
    "is_active": true,
    "created_at": "...",
    "updated_at": "..."
  }
}

2. List all users subscribed to Pro

GET /subscriptions/pro-users

Description: Returns all users who currently have an active subscription to the "Pro" plan.

Request:

GET /api/subscriptions/pro-users
Authorization: Bearer {token}


Response:

{
  "plan": "Pro",
  "count": 4,
  "users": [
    {"id": 1, "name": "Hassan", "email": "hassan@example.com"},
    {"id": 7, "name": "Mona", "email": "mona@example.com"}
  ]
}

3. List all plans

GET /plans

Description: Returns a list of all available subscription plans.

Request:

GET /api/plans
Authorization: Bearer {token}


Response:

{
  "count": 3,
  "plans": [
    {"id":1,"name":"Pro","duration":30,"price":29.99},
    {"id":2,"name":"Basic","duration":30,"price":9.99},
    {"id":3,"name":"Enterprise","duration":365,"price":299.99}
  ]
}

4. Get a specific plan by ID

GET /plan/{id}

Description: Returns a specific plan's details.

Request:

GET /api/plan/1
Authorization: Bearer {token}


Response:

{
  "plan": {"id":1,"name":"Pro","duration":30,"price":29.99}
}


Error Response (if plan not found):

{
  "message": "Plan not found."
}

Notes

Dates: starts_at and ends_at are in YYYY-MM-DD format.

Active subscription: is_active = true indicates the user currently has access.

Plan duration: duration is in days.

## Blogs

### Get All Blogs
**Endpoint:** `GET /api/blogs`

**Test Request:**
```bash
GET /api/blogs
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Getting Started with Laravel",
      "slug": "getting-started-with-laravel",
      "excerpt": "Learn the basics of Laravel framework",
      "content": "Full blog content here...",
      "author": "John Doe",
      "published_at": "2024-01-10",
      "cover_image": "http://example.com/images/blog1.jpg"
    }
  ]
}
```

### Get Single Blog
**Endpoint:** `GET /api/blogs/{id}`

**Test Request:**
```bash
GET /api/blogs/1
```

---

## Complete Testing Workflow

### Step-by-Step Test Guide

**1. Register & Login**
```bash
# Register
POST /api/register
{
  "name": "Test User",
  "email": "test@example.com",
  "phone": "01234567890",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "user",
  "bio": "Test account",
  "avatar": null
}

# Login
POST /api/login
{
  "email": "test@example.com",
  "password": "password123"
}

# Save the token from response
```

**2. Create Organization**
```bash
POST /api/organizations
Authorization: Bearer {token}

{
  "name": "Test Corp",
  "sector": "Technology",
  "established_at": "2020-01-01",
  "email": "info@testcorp.com",
  "phone": "0123456789",
  "address": "123 Test St",
  "license_number": "TEST123",
  "executive_name": "Test Executive"
}
```

**3. Get Shield Questions**
```bash
GET /api/shield/questions
Authorization: Bearer {token}
```

**4. Upload Attachment (Optional)**
```bash
POST /api/shield/attachment/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [select a PDF or image file]
```

**5. Save Draft Answers**
```bash
POST /api/shield/save
Authorization: Bearer {token}

{
  "answers": [
    {"question_id": 1, "answer": true, "attachment": null},
    {"question_id": 2, "answer": false, "attachment": "http://..."}
  ]
}
```

**6. Submit Final Answers**
```bash
POST /api/shield/submit
Authorization: Bearer {token}

{
  "axis_id": 1,
  "questions": [
    {
      "question_id": 1,
      "answer": true
    },
    {
      "question_id": 2,
      "answer": false
    },
    {
      "question_id": 3,
      "answer": true
    },
    {
      "question_id": 4,
      "answer": true
    }
  ],
  "attachments": [
    "https://your-domain.com/storage/uploads/axis1-doc1.pdf",
    "https://your-domain.com/storage/uploads/axis1-doc2.pdf",
    "https://your-domain.com/storage/uploads/axis1-doc3.pdf"
  ]
}
```

**7. Download PDF Results**
```bash
GET /api/shield/download-results
Authorization: Bearer {token}
```

**8. Check Analytics (Public)**
```bash
GET /api/shield/analytics
```

**9. View Organizations List (Public)**
```bash
GET /api/shield/organizations?page=1&limit=10
```

---

## Postman Collection

### Import these examples into Postman:

**Base URL:** `http://your-domain.com/api`

**Environment Variables:**
- `base_url`: `http://your-domain.com/api`
- `token`: (Set after login)

**Collection Structure:**
```
📁 Shield API
  📁 Auth
    - Register
    - Login
    - Logout
  📁 Organizations
    - Create Organization
    - Get All Organizations
    - Get Single Organization
    - Update Organization
    - Delete Organization
  📁 Shield (Public)
    - Get Analytics
    - Get Organizations List
  📁 Shield (Protected)
    - Get Questions
    - Save Answers
    - Submit Answers
    - Upload Attachment
    - Download Results
  📁 Podcasts
    - Get All Podcasts
    - Get Single Podcast
  📁 Releases
    - Get All Releases
    - Get Single Release
    - Download Release
  📁 Blogs
    - Get All Blogs
    - Get Single Blog
```

---

## Test Data Setup

### Quick Start - Create Test Account

**Step 1: Register User**
```bash
POST /api/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "phone": "01000000001",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "user",
  "bio": "Test user for the platform",
  "avatar": null
}
```

**Step 2: Login**
```bash
POST /api/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```
GET /api/me

Content-Type: application/json

**Response:**
```json
{
 {
    "user": {
        "id": 2,
        "name": "Hassan Mohamed",
        "email": "hassan@example.com",
        "phone": null,
        "email_verified_at": null,
        "user_priviliages": null,
        "created_at": "2025-11-16T06:36:12.000000Z",
        "updated_at": "2025-11-16T06:36:12.000000Z"
    },
    "organization": {
        "id": 1,
        "name": "TechCorp",
        "sector": "IT",
        "email": "info@techcorp.com",
        "phone": "1234567890",
        "address": "123 Main St",
        "license_number": "LIC12345",
        "executive_name": "John Doe",
        "status": "pending",
        "shield_percentage": null,
        "shield_rank": null,
        "certificate_final_score": null,
        "certificate_final_rank": null,
        "established_at": "2020-05-01T00:00:00.000000Z",
        "created_at": "2025-11-16T06:37:25.000000Z",
        "updated_at": "2025-11-16T06:37:25.000000Z"
    },
    "token": {
        "name": "api_token",
        "abilities": [
            "*"
        ],
        "created_at": "2025-11-16T06:51:13.000000Z",
        "last_used_at": "2025-11-16T06:57:24.000000Z"
    },
    "flags": {
        "email_verified": false,
        "has_organization": true,
        "organization_status": "pending",
        "can_access_features": false
    }
}
  }


**Response:**
```json
{
  "success": true,
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com"
  }
}
```

**Step 3: Create Organization**
```bash
POST /api/organizations
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "TechCorp",
  "sector": "IT",
  "established_at": "2020-05-01",
  "email": "info@techcorp.com",
  "phone": "1234567890",
  "address": "123 Main St",
  "license_number": "LIC12345",
  "executive_name": "John Doe"
}
```

---

## Authentication Endpoints

### 1. Register User

Creates a new user account and sends email verification.

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `email`: required, valid email, max 255 characters, unique
- `phone`: optional, string, max 20 characters, unique
- `password`: required, min 8 characters, must match confirmation
- `password_confirmation`: required

**Success Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully. Please verify your email within 10 minutes.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "email_verified_at": null
  }
}
```

**Error Responses:**

*Validation Error (422):*
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  },
  "error_type": "validation_error"
}
```

*Server Error (500):*
```json
{
  "success": false,
  "message": "Registration failed",
  "error": "Error details (only in debug mode)",
  "error_type": "server_error"
}
```

---

### 2. Login

Authenticates user and returns access token.

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123!"
}
```

**Validation Rules:**
- `email`: required, valid email
- `password`: required, string

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|laravel_sanctum_abc123xyz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "email_verified_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Error Responses:**

*Invalid Credentials (401):*
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

*Email Not Verified (401):*
```json
{
  "success": false,
  "message": "Please verify your email before logging in"
}
```

**Usage:**
Store the token and include it in subsequent requests:
```
Authorization: Bearer 1|laravel_sanctum_abc123xyz...
```

---

### 3. Logout

Revokes all user tokens.

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Unauthenticated",
  "error_type": "authentication_error"
}
```

---

## Email Verification Endpoints

### 4. Verify Email

Verifies user's email address using the token from the email.

**Endpoint:** `GET /api/email/verify?token={token}`

**Query Parameters:**
- `token` (required): 64-character verification token

**Success Response (200):**
```json
{
  "success": true,
  "message": "Email verified successfully! You can now login.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Error Responses:**

*Invalid Token (400):*
```json
{
  "success": false,
  "message": "Invalid verification token"
}
```

*Already Verified (400):*
```json
{
  "success": false,
  "message": "Email already verified"
}
```

*Token Expired (400):*
```json
{
  "success": false,
  "message": "Verification token has expired. Please request a new one."
}
```

*Missing Token (422):*
```json
{
  "success": false,
  "message": "Verification token is required"
}
```

---

### 5. Resend Verification Email

Sends a new verification email to the user.

**Endpoint:** `POST /api/email/resend`

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Validation Rules:**
- `email`: required, valid email, must exist in users table

**Success Response (200):**
```json
{
  "success": true,
  "message": "Verification email sent successfully. Please check your inbox."
}
```

**Error Responses:**

*User Not Found (400):*
```json
{
  "success": false,
  "message": "User not found"
}
```

*Already Verified (400):*
```json
{
  "success": false,
  "message": "Email is already verified"
}
```

*Rate Limited (400):*
```json
{
  "success": false,
  "message": "Please wait before requesting another verification email"
}
```

**Note:** Rate limit is 2 minutes between requests.

---

## Password Reset Endpoints

### 6. Forgot Password

Sends a password reset link to the user's email.

**Endpoint:** `POST /api/password/forgot`

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Validation Rules:**
- `email`: required, valid email

**Success Response (200):**
```json
{
  "success": true,
  "message": "If your email exists in our system, you will receive a password reset link shortly."
}
```

**Note:** For security, the response is the same whether the email exists or not.

**Error Responses:**

*Rate Limited (400):*
```json
{
  "success": false,
  "message": "Please wait before requesting another password reset email"
}
```

*Server Error (500):*
```json
{
  "success": false,
  "message": "Failed to send password reset email"
}
```

**Rate Limit:** 2 minutes between requests per email.

---

### 7. Reset Password

Resets the user's password using the token from the email.

**Endpoint:** `POST /api/password/reset`

**Request Body:**
```json
{
  "email": "john@example.com",
  "token": "abc123xyz...64-character-token",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Validation Rules:**
- `email`: required, valid email
- `token`: required, string, 64 characters
- `password`: required, min 8 characters, must match confirmation
- `password_confirmation`: required

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password has been reset successfully. Please login with your new password."
}
```

**Error Responses:**

*Invalid Token/Email (400):*
```json
{
  "success": false,
  "message": "Invalid reset token or email"
}
```

*Token Expired (400):*
```json
{
  "success": false,
  "message": "Password reset token has expired. Please request a new one."
}
```

*Validation Error (422):*
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "password": ["The password must be at least 8 characters."]
  },
  "error_type": "validation_error"
}
```

**Note:** After successful password reset, all existing user tokens are revoked.

---

### 8. Verify Reset Token (Optional)

Verifies if a password reset token is valid before showing the reset form.

**Endpoint:** `GET /api/password/verify-token?email={email}&token={token}`

**Query Parameters:**
- `email` (required): User's email address
- `token` (required): Password reset token



**Response:**
```json
{
  "success": true,
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz",
  "user": {
    "id": 1,
    "name": "Hassan Ali",
    "email": "hassan@example.com"
  }
}
```

### Logout
**Endpoint:** `POST /api/logout`
**Authentication:** Required

---

## Organizations CRUD

### Create Organization
**Endpoint:** `POST /api/organizations`
**Authentication:** Required

**Request Body:**
```json
{
  "name": "TechCorp",
  "sector": "IT",
  "established_at": "2020-05-01",
  "email": "info@techcorp.com",
  "phone": "1234567890",
  "address": "123 Main St",
  "license_number": "LIC12345",
  "executive_name": "John Doe"
}
```

### Get All Organizations
**Endpoint:** `GET /api/organizations`
**Authentication:** Required

### Get Single Organization
**Endpoint:** `GET /api/organizations/{id}`
**Authentication:** Required

### Update Organization
**Endpoint:** `PUT /api/organizations/{id}`
**Authentication:** Required

### Delete Organization
**Endpoint:** `DELETE /api/organizations/{id}`
**Authentication:** Required

---

## Shield Module

### Test Data for Shield

**Sample Questions Response:**
```json
{
  "success": true,
  "axes": [
    {
      "id": "1",
      "title": "Data Protection",
      "description": "Questions related to data security and privacy",
      "questions": [
        {
          "id": "1",
          "question": "Does your organization have a data protection policy?",
          "has_attachment": false,
          "current_answer": null,
          "attachment": null
        },
        {
          "id": "2",
          "question": "Do you conduct regular security audits?",
          "has_attachment": false,
          "current_answer": null,
          "attachment": null
        }
      ]
    },
    {
      "id": "2",
      "title": "Incident Response",
      "description": "Questions related to incident handling",
      "questions": [
        {
          "id": "3",
          "question": "Do you have an incident response team?",
          "has_attachment": false,
          "current_answer": null,
          "attachment": null
        },
        {
          "id": "4",
          "question": "Is there a documented incident response plan?",
          "has_attachment": false,
          "current_answer": null,
          "attachment": null
        }
      ]
    }
  ]
}
```

---

## Questions & Answers

### 1. Get Questions with Saved Answers

Retrieve all shield assessment questions organized by axes, including the user's previously saved answers.

**Endpoint:** `GET /api/shield/questions`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "axes": [
    {
      "id": "1",
      "title": "Data Protection",
      "description": "Questions related to data security and privacy",
      "questions": [
        {
          "id": "1",
          "question": "Does your organization have a data protection policy?",
          "has_attachment": true,
          "current_answer": "true",
          "attachment": "http://example.com/storage/shield_attachments/1/file.pdf"
        },
        {
          "id": "2",
          "question": "Do you conduct regular security audits?",
          "has_attachment": false,
          "current_answer": null,
          "attachment": null
        }
      ]
    }
  ]
}
```

**Response Fields:**
- `current_answer`: `"true"`, `"false"`, or `null` (not answered)
- `has_attachment`: Boolean indicating if an attachment exists
- `attachment`: Full URL to the uploaded file or `null`

---

### 2. Save Answers (Draft)

Save current answers without requiring all fields to be filled. Allows users to save progress and continue later.

**Endpoint:** `POST /api/shield/save`

**Authentication:** Required

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "answer": true,
      "attachment": "http://example.com/storage/shield_attachments/1/file.pdf"
    },
    {
      "question_id": 2,
      "answer": false,
      "attachment": null
    },
    {
      "question_id": 3,
      "answer": null
    }
  ]
}
```

**Validation Rules:**
- `answers`: required, array
- `answers.*.question_id`: required, must exist in `shield_axes_questions` table
- `answers.*.answer`: nullable, boolean
- `answers.*.attachment`: nullable, string (URL from upload endpoint)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Answers saved successfully"
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "No organization found for this user"
}
```

**Notes:**
- Partial submissions are allowed
- Does NOT calculate scores or ranks
- Can be called multiple times
- Previous answers are preserved and merged with new ones

---

### 3. Submit Final Answers

Submit final answers for evaluation. All questions must be answered.

**Endpoint:** `POST /api/shield/submit`

**Authentication:** Required

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "answer": true,
      "attachment": "http://example.com/storage/shield_attachments/1/policy.pdf"
    },
    {
      "question_id": 2,
      "answer": false,
      "attachment": null
    }
  ]
}
```

**Validation Rules:**
- `answers`: required, array
- `answers.*.question_id`: required, must exist in `shield_axes_questions` table
- `answers.*.answer`: **required**, boolean
- `answers.*.attachment`: nullable, string

**Success Response (200):**
```json
{
  "success": true,
  "message": "Answers submitted successfully",
  "total_score": 75.50,
  "rank": "silver"
}
```

**Error Response - Missing Questions (422):**
```json
{
  "success": false,
  "message": "All questions must be answered before submitting",
  "missing_questions": [3, 5, 7]
}
```

**Error Response - No Organization (404):**
```json
{
  "success": false,
  "message": "No organization found for this user"
}
```

**Ranking System:**
- **Gold**: ≥ 90%
- **Silver**: ≥ 70%
- **Bronze**: ≥ 50%
- **No Rank**: < 50%

**Notes:**
- ALL questions must be answered
- Calculates axis scores and overall organization score
- Updates organization rank
- Can be resubmitted to update answers

---

## Attachments

### Upload Attachment

Upload a file to be attached to a question answer.

**Endpoint:** `POST /api/shield/attachment/upload`

**Authentication:** Required

**Request:**
- **Content-Type:** `multipart/form-data`
- **Body:**
  - `file`: File upload

**Validation Rules:**
- File is required
- Allowed types: `pdf`, `docx`, `doc`, `jpg`, `jpeg`, `png`, `xlsx`, `xls`
- Max size: 10MB (10240 KB)

**Success Response (200):**
```json
{
  "success": true,
  "file_url": "http://example.com/storage/shield_attachments/1/document.pdf",
  "file_path": "shield_attachments/1/document.pdf"
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "No organization found for this user"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "The file must be a file of type: pdf, docx, doc, jpg, jpeg, png, xlsx, xls."
}
```

**Usage Flow:**
1. Upload file using this endpoint
2. Receive `file_url` in response
3. Include `file_url` in the `attachment` field when saving/submitting answers

---

## Analytics

### Get Shield Statistics

Retrieve overall shield program statistics and analytics.

**Endpoint:** `GET /api/shield/analytics`

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "total_organizations_awarded": 45,
  "highest_rate": 95.50,
  "average_rate": 68.75,
  "organizations_completed_ratio": 72.30
}
```

**Response Fields:**
- `total_organizations_awarded`: Number of organizations with any rank (bronze, silver, or gold)
- `highest_rate`: Highest shield percentage among all organizations
- `average_rate`: Average shield percentage across all organizations
- `organizations_completed_ratio`: Percentage of organizations that have started the assessment

---

## Organizations

### Get Organizations List

Retrieve a paginated, filterable list of organizations with their shield performance.

**Endpoint:** `GET /api/shield/organizations`

**Authentication:** Required

**Query Parameters:**
- `page` (optional): Page number, integer, min: 1, default: 1
- `limit` (optional): Items per page, integer, min: 1, max: 100, default: 10
- `query` (optional): Search by organization name or website, string, max: 255
- `year` (optional): Filter by year, integer, min: 2000, max: 2100
- `grade` (optional): Filter by grade, enum: `acceptable`, `good`, `very_good`, `excellent`
- `region` (optional): Filter by region, string, max: 255

**Example Request:**
```
GET /api/shield/organizations?page=1&limit=20&grade=excellent&region=North
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "organization_name": "TechCorp Inc.",
      "organization_website": "https://techcorp.com",
      "grade": "excellent",
      "region": "North America",
      "year": 2024,
      "rate": 92.50
    },
    {
      "organization_name": "DataShield LLC",
      "organization_website": "https://datashield.com",
      "grade": "very_good",
      "region": "Europe",
      "year": 2023,
      "rate": 78.25
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 45,
    "per_page": 10
  }
}
```

**Grade Mapping:**
- `excellent`: Gold rank (≥ 90%)
- `very_good`: Silver rank (≥ 70%)
- `good`: Bronze rank (≥ 50%)
- `acceptable`: Below bronze but has a score (> 0%)

**Notes:**
- Only organizations with shield data are returned
- Search query matches against organization name and website

---

## Download Results

### Download Shield Results as PDF

Generate and download a PDF report of the organization's shield assessment results.

**Endpoint:** `GET /api/shield/download-results`

**Authentication:** Required

**Response:**
- **Content-Type:** `application/pdf`
- **Content-Disposition:** `attachment; filename="shield-results-{OrganizationName}-{Date}.pdf"`

**Response Body:** Binary PDF file

**Error Response (404):**
```json
{
  "success": false,
  "message": "No organization found for this user"
}
```

**PDF Contents:**
- Organization name
- Total score and rank
- Date of generation
- Detailed breakdown by axis:
  - Axis title and description
  - Individual questions and answers
  - Axis score

**Example Filename:**
```
shield-results-TechCorp-2024-11-10.pdf
```

---

## Error Responses

All endpoints may return the following error responses:

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "answers.0.question_id": [
      "The selected answers.0.question_id is invalid."
    ]
  }
}
```

### Unauthenticated (401)
```json
{
  "message": "Unauthenticated."
}
```

### Internal Server Error (500)
```json
{
  "success": false,
  "message": "Failed to submit answers: [error details]"
}
```

---

## Complete Workflow Example

### Step 1: Get Questions
```bash
GET /api/shield/questions
```

### Step 2: Upload Attachments (if needed)
```bash
POST /api/shield/attachment/upload
Content-Type: multipart/form-data

file: [binary]
```

### Step 3: Save Progress (Draft)
```bash
POST /api/shield/save
Content-Type: application/json

{
  "answers": [
    {"question_id": 1, "answer": true, "attachment": "..."},
    {"question_id": 2, "answer": null}
  ]
}
```

### Step 4: Submit Final Answers
```bash
POST /api/shield/submit
Content-Type: application/json

{
  "answers": [
    {"question_id": 1, "answer": true, "attachment": "..."},
    {"question_id": 2, "answer": false},
    {"question_id": 3, "answer": true}
  ]
}
```

### Step 5: Download Results
```bash
GET /api/shield/download-results
```

---

---

## Sample Test Data

### Ready-to-Use JSON Payloads

#### Register User
```json
{
  "name": "John Smith",
  "email": "john.smith@example.com",
  "phone": "01012345678",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "user",
  "bio": "Frontend developer testing the API",
  "avatar": null
}
```

#### Create Organization
```json
{
  "name": "Digital Solutions Ltd",
  "sector": "Information Technology",
  "established_at": "2018-03-15",
  "email": "contact@digitalsolutions.com",
  "phone": "0201234567",
  "address": "456 Innovation Street, Cairo, Egypt",
  "license_number": "DSL2018456",
  "executive_name": "Ahmed Hassan"
}
```

#### Save Draft Answers (4 questions example)
```json
{
  "answers": [
    {
      "question_id": 1,
      "answer": true,
      "attachment": null
    },
    {
      "question_id": 2,
      "answer": true,
      "attachment": null
    },
    {
      "question_id": 3,
      "answer": false,
      "attachment": null
    },
    {
      "question_id": 4,
      "answer": null,
      "attachment": null
    }
  ]
}
```

#### Submit Complete Answers (All 4 questions)
```json
{
  "answers": [
    {
      "question_id": 1,
      "answer": true,
      "attachment": "http://example.com/storage/shield_attachments/1/data-policy.pdf"
    },
    {
      "question_id": 2,
      "answer": true,
      "attachment": null
    },
    {
      "question_id": 3,
      "answer": false,
      "attachment": null
    },
    {
      "question_id": 4,
      "answer": true,
      "attachment": "http://example.com/storage/shield_attachments/1/incident-plan.pdf"
    }
  ]
}
```

---

## Expected Score Calculation

### Understanding Shield Scoring

**Axis Score Calculation:**
- Each axis has 4 questions (typically)
- Each question = 25% of axis score
- Answer "true" = contributes to score
- Answer "false" = does not contribute

**Example:**
```
Axis 1 (Data Protection):
- Question 1: true  → 25%
- Question 2: true  → 25%
- Question 3: false → 0%
- Question 4: true  → 25%
Total Axis Score = 75%

Axis 2 (Incident Response):
- Question 1: true  → 25%
- Question 2: true  → 25%
- Question 3: true  → 25%
- Question 4: true  → 25%
Total Axis Score = 100%

Organization Total = (75% + 100%) / 2 axes = 87.5%
Rank = Silver (70% ≤ score < 90%)
```

**Ranking Thresholds:**
- 🥇 **Gold**: 90% - 100%
- 🥈 **Silver**: 70% - 89%
- 🥉 **Bronze**: 50% - 69%
- ❌ **No Rank**: 0% - 49%

---

## Error Responses Reference

### Common Error Codes

**400 Bad Request**
```json
{
  "success": false,
  "message": "Invalid request format"
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**404 Not Found**
```json
{
  "success": false,
  "message": "No organization found for this user"
}
```

**422 Validation Error**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

**422 Missing Questions**
```json
{
  "success": false,
  "message": "All questions must be answered before submitting",
  "missing_questions": [3, 4]
}
```

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Failed to submit answers: Database connection error"
}
**Blogs** 
GET api/models 

/     //FRO ALL models 
/id for models by ID 


## Testing Checklist

### Before Testing
- [ ] Backend server is running
- [ ] Database is migrated and seeded
- [ ] Storage link is created (`php artisan storage:link`)
- [ ] API base URL is correct

### Authentication Flow
- [ ] Register new user
- [ ] Login with credentials
- [ ] Save token
- [ ] Test protected endpoint with token
- [ ] Test logout

### Shield Module Flow
- [ ] Create organization
- [ ] Get questions (empty answers initially)
- [ ] Upload attachment file
- [ ] Save draft answers (partial)
- [ ] Get questions again (should show saved answers)
- [ ] Submit complete answers
- [ ] Verify score and rank in response
- [ ] Download PDF results
- [ ] Check analytics endpoint
- [ ] Check organizations list

### Edge Cases to Test
- [ ] Submit without all questions answered
- [ ] Submit without creating organization
- [ ] Upload invalid file type
- [ ] Upload file > 10MB
- [ ] Resubmit to update answers
- [ ] Filter organizations by grade
- [ ] Pagination on organizations list

---

## Quick Reference Card

| Action | Method | Endpoint | Auth Required |
|--------|--------|----------|---------------|
| **Authentication** | | | |
| Register | POST | `/register` | ❌ |
| Login | POST | `/login` | ❌ |
| Logout | POST | `/logout` | ✅ |
| **Organizations** | | | |
| Create | POST | `/organizations` | ✅ |
| List | GET | `/organizations` | ✅ |
| Show | GET | `/organizations/{id}` | ✅ |
| Update | PUT | `/organizations/{id}` | ✅ |
| Delete | DELETE | `/organizations/{id}` | ✅ |
| **Shield (Public)** | | | |
| Analytics | GET | `/shield/analytics` | ❌ |
| Org List | GET | `/shield/organizations` | ❌ |
| **Shield (Protected)** | | | |
| Get Questions | GET | `/shield/questions` | ✅ |
| Save Draft | POST | `/shield/save` | ✅ |
| Submit Final | POST | `/shield/submit` | ✅ |
| Upload File | POST | `/shield/attachment/upload` | ✅ |
| Download PDF | GET | `/shield/download-results` | ✅ |
| **Content** | | | |
| Podcasts | GET | `/podcasts` | ❌ |
| Podcast Detail | GET | `/podcasts/{id}` | ❌ |
| Releases | GET | `/releases` | ❌ |
| Release Detail | GET | `/releases/{id}` | ❌ |
| Download Release | GET | `/releases/{id}/download` | ❌ |
| Blogs | GET | `/blogs` | ❌ |
| Blog Detail | GET | `/blogs/{id}` | ❌ |

---

### [Certificate](#certificate)

## Base URL
```
/api/certificates
```

All endpoints require Bearer token:
```
Authorization: Bearer {your_sanctum_token}
```

---

## 👤 User/Organization APIs

### ➊ Get Questions by Path

Get all questions for a specific certification path.

**Request:**
```http
GET /api/certificates/{path}/questions
```

**Path Parameters:**
- `path`: `strategic`, `operational`, or `hr`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Strategic Planning Axis",
      "description": "Questions about strategic planning",
      "path": "strategic",
      "weight": 1.5,
      "questions": [
        {
          "id": 1,
          "question_text": "Does your organization have a clear strategic plan?",
          "options": ["Yes, documented", "Yes, verbal", "No"],
          "points_mapping": {
            "Yes, documented": 10,
            "Yes, verbal": 5,
            "No": 0
          },
          "weight": 2,
          "attachment_required": true,
          "path": "strategic"
        }
      ]
    }
  ]
}
```

---

### ➋ Save Answers

Save one or multiple answers. Files can be uploaded directly or URLs provided.

**Request:**
```http
POST /api/certificates/{path}/answers
Content-Type: multipart/form-data OR application/json
```

**Request Body (with file upload):**
```json
{
  "answers": [
    {
      "question_id": 1,
      "selected_option": "Yes, documented",
      "attachment": "[FILE UPLOAD]"
    },
    {
      "question_id": 2,
      "selected_option": "Option B"
    }
  ]
}
```

**Request Body (with URL):**
```json
{
  "answers": [
    {
      "question_id": 1,
      "selected_option": "Yes, documented",
      "attachment_url": "https://yourdomain.com/storage/certificate_attachments/strategic/1/doc.pdf"
    }
  ]
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم حفظ الإجابات بنجاح ✅ (تم تجاهل 2 إجابات محفوظة مسبقاً)",
  "data": {
    "path": "strategic",
    "saved_count": 3,
    "skipped_count": 2,
    "answered_questions": 8,
    "total_questions": 10,
    "is_complete": false
  }
}
```

**Notes:**
- ✅ Answers can be saved one-by-one or in bulk
- 🔒 Once saved, answers **cannot be modified**
- ⏭️ Attempting to re-save skips the answer (no error)
- 📎 Either upload file OR provide URL (not both)

---

### ➌ Submit Certificate Path

Submit a completed path to calculate the score.

**Request:**
```http
POST /api/certificates/{path}/submit
```

**Success Response - Path Completed (200):**
```json
{
  "success": true,
  "message": "تم إرسال مسار strategic بنجاح ✅",
  "data": {
    "path": "strategic",
    "path_score": 85.5,
    "all_paths_completed": false,
    "completed_paths": 1,
    "total_paths": 3,
    "overall_score": null,
    "overall_rank": null
  }
}
```

**Success Response - All Paths Completed (200):**
```json
{
  "success": true,
  "message": "تم إرسال مسار hr بنجاح! 🎉 جميع المسارات مكتملة ✅",
  "data": {
    "path": "hr",
    "path_score": 92.0,
    "all_paths_completed": true,
    "completed_paths": 3,
    "total_paths": 3,
    "overall_score": 265.5,
    "overall_rank": "gold"
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Cannot submit. You have answered 5 out of 10 questions."
}
```

**Ranking System:**
- 💎 **Diamond**: ≥86%
- 🥇 **Gold**: ≥76%
- 🥈 **Silver**: ≥66%
- 🥉 **Bronze**: ≥55%

---

### ➏ Bulk Upload Answers

Upload multiple answers at once using attachment URLs (no file uploads).

**Request:**
```http
POST /api/certificates/{path}/upload-answers
Content-Type: application/json
```

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "selected_option": "Option A",
      "attachment_url": "https://yourdomain.com/storage/file1.pdf"
    },
    {
      "question_id": 2,
      "selected_option": "Option B",
      "attachment_url": "https://yourdomain.com/storage/file2.pdf"
    },
    {
      "question_id": 3,
      "selected_option": "Option C"
    }
  ]
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم رفع الإجابات بنجاح ✅ (تم تجاهل 2 إجابات محفوظة مسبقاً)",
  "data": {
    "path": "strategic",
    "saved_count": 8,
    "skipped_count": 2,
    "answered_questions": 10,
    "total_questions": 10,
    "is_complete": true,
    "errors": []
  }
}
```

**Use Case:** Importing answers from external systems or Excel sheets.

---

### ➐ Download Path Data

Get detailed certificate data for a specific path.

**Request:**
```http
GET /api/certificates/{path}/download
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "organization": {
      "id": 1,
      "name": "ABC Company",
      "email": "contact@abc.com"
    },
    "path": "strategic",
    "path_score": 85.5,
    "axes": [
      {
        "axis_id": 1,
        "axis_name": "Strategic Planning",
        "axis_description": "Strategic planning questions",
        "axis_weight": 1.5,
        "questions": [
          {
            "question_id": 1,
            "question_text": "Question text?",
            "options": ["Option 1", "Option 2", "Option 3"],
            "selected_option": "Option 1",
            "points": 10,
            "final_points": 20,
            "weight": 2,
            "attachment_path": "https://domain.com/storage/file.pdf",
            "attachment_required": true,
            "answered": true
          }
        ]
      }
    ],
    "summary": {
      "answered_questions": 10,
      "total_questions": 10,
      "completion_percentage": 100,
      "is_complete": true
    }
  }
}
```

---

### ➑ Download Overall Data

Get complete certificate data for all paths.

**Request:**
```http
GET /api/certificates/download
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "organization": {
      "id": 1,
      "name": "ABC Company",
      "email": "contact@abc.com",
      "phone": "+1234567890"
    },
    "overall_score": 265.5,
    "overall_rank": "gold",
    "completed_paths": 3,
    "total_paths": 3,
    "all_paths_completed": true,
    "paths_data": {
      "strategic": {
        "organization": {...},
        "path": "strategic",
        "path_score": 85.5,
        "axes": [...]
      },
      "operational": {...},
      "hr": {...}
    }
  }
}
```

---

### ➒ Get User Summary

Get certificate progress summary with completion rates and states.

**Request:**
```http
GET /api/certificates/summary
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "organization": {
      "id": 1,
      "name": "ABC Company",
      "email": "contact@abc.com"
    },
    "overall_completion_rate": 66.67,
    "overall_score": 245.5,
    "overall_rank": "gold",
    "paths": {
      "strategic": {
        "path_name": "strategic",
        "state": "complete",
        "completion_rate": 100,
        "answered_questions": 10,
        "total_questions": 10,
        "current_score": 85.5,
        "max_possible_score": 100
      },
      "operational": {
        "path_name": "operational",
        "state": "in_review",
        "completion_rate": 60,
        "answered_questions": 6,
        "total_questions": 10,
        "current_score": 45.0,
        "max_possible_score": 100
      },
      "hr": {
        "path_name": "hr",
        "state": "not_started",
        "completion_rate": 0,
        "answered_questions": 0,
        "total_questions": 10,
        "current_score": 0,
        "max_possible_score": 100
      }
    },
    "progress_summary": {
      "not_started": 1,
      "in_review": 1,
      "complete": 1,
      "total_paths": 3
    }
  }
}
```

**Path States:**
- 🆕 **not_started**: 0% completion
- 📝 **in_review**: 1-99% completion
- ✅ **complete**: 100% completion

---

### ➓ Upload File Only

Upload a file and receive its URL without saving an answer.

**Request:**
```http
POST /api/certificates/{path}/upload-file
Content-Type: multipart/form-data
```

**Form Data:**
```
file: [YOUR FILE]
```

**Allowed Types:** `pdf`, `doc`, `docx`, `jpg`, `jpeg`, `png`  
**Max Size:** 5MB (5120KB)

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم رفع الملف بنجاح ✅",
  "data": {
    "attachment_path": "certificate_attachments/strategic/123/document.pdf",
    "attachment_url": "https://yourdomain.com/storage/certificate_attachments/strategic/123/document.pdf"
  }
}
```

**Usage Workflow:**
1. Upload file → receive `attachment_url`
2. Save answer with `attachment_url`

---

## 👨‍💼 Admin APIs

### ➍ Get Analytics

Get comprehensive analytics about all organizations.

**Request:**
```http
GET /api/admin/certificates/analytics
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_organizations": 50,
    "completed_all_paths": 15,
    "partial_completion": 25,
    "not_started": 10,
    "organizations": {
      "completed_all": [
        {
          "id": 1,
          "name": "Company A",
          "email": "companya@example.com",
          "paths": {
            "strategic": {
              "answered": 10,
              "total": 10,
              "completed": true,
              "score": 85.5,
              "percentage": 100
            },
            "operational": {
              "answered": 10,
              "total": 10,
              "completed": true,
              "score": 90.0,
              "percentage": 100
            },
            "hr": {
              "answered": 10,
              "total": 10,
              "completed": true,
              "score": 90.0,
              "percentage": 100
            }
          },
          "overall_score": 265.5,
          "overall_rank": "gold",
          "completed_paths": 3,
          "total_paths": 3
        }
      ],
      "partial_completion": [...],
      "not_started": [...]
    }
  }
}
```
GET 
---

### ➎ Get All Organizations

Get list of all registered organizations with progress.

**Request:**
```http
GET /api/admin/certificates/organizations
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total": 50,
    "organizations": [
      {
        "id": 1,
        "name": "Company Name",
        "email": "company@example.com",
        "phone": "+1234567890",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "paths": {
          "strategic": {
            "answered": 10,
            "total": 10,
            "completed": true,
            "score": 85.5,
            "percentage": 100
          },
          "operational": {
            "answered": 7,
            "total": 10,
            "completed": false,
            "score": 45.0,
            "percentage": 70
          },
          "hr": {
            "answered": 0,
            "total": 10,
            "completed": false,
            "score": 0,
            "percentage": 0
          }
        },
        "overall_score": 265.5,
        "overall_rank": "gold"
      }
    ]
  }
}
```

---

## 🎯 Complete Usage Example

### Scenario: Organization Completing Certificate

```bash
# Step 1: Upload attachments first (optional)
curl -X POST "https://api.example.com/api/certificates/strategic/upload-file" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@strategic-plan.pdf"

# Response: { "attachment_url": "https://..." }

# Step 2: Get questions
curl -X GET "https://api.example.com/api/certificates/strategic/questions" \
  -H "Authorization: Bearer TOKEN"

# Step 3: Save answers (batch)
curl -X POST "https://api.example.com/api/certificates/strategic/answers" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": [
      {
        "question_id": 1,
        "selected_option": "Yes, documented",
        "attachment_url": "https://api.example.com/storage/..."
      },
      {
        "question_id": 2,
        "selected_option": "Monthly"
      },
      {
        "question_id": 3,
        "selected_option": "Board approved"
      }
    ]
  }'

# Step 4: Check progress
curl -X GET "https://api.example.com/api/certificates/summary" \
  -H "Authorization: Bearer TOKEN"

# Step 5: Submit when complete
curl -X POST "https://api.example.com/api/certificates/strategic/submit" \
  -H "Authorization: Bearer TOKEN"

# Step 6: Repeat for operational and hr paths

# Step 7: Download final certificate
curl -X GET "https://api.example.com/api/certificates/download" \
  -H "Authorization: Bearer TOKEN"
```

---

## 📝 Key Features Summary

| Feature | Endpoint | Method |
|---------|----------|--------|
| Get Questions | `/certificates/{path}/questions` | GET |
| Save Answers | `/certificates/{path}/answers` | POST |
| Submit Path | `/certificates/{path}/submit` | POST |
| Bulk Upload | `/certificates/{path}/upload-answers` | POST |
| Upload File | `/certificates/{path}/upload-file` | POST |
| Path Data | `/certificates/{path}/download` | GET |
| Overall Data | `/certificates/download` | GET |
| Summary | `/certificates/summary` | GET |
| Analytics (Admin) | `/admin/certificates/analytics` | GET |
| All Orgs (Admin) | `/admin/certificates/organizations` | GET |

---

## ⚠️ Important Rules

1. **Immutability**: Answers cannot be modified after saving
2. **Sequential**: Must answer all questions before submitting
3. **Final Rank**: Only calculated when all 3 paths are complete
4. **File Size**: Maximum 5MB per attachment
5. **File Types**: pdf, doc, docx, jpg, jpeg, png only
6. **Paths**: Must be `strategic`, `operational`, or `hr`



POST api/care-requests

{
    "entity_name" : "",
    "name" : "",
    "phone": "",
    "entity_type" : "",
    "message" : ""
}


POST api/contactus


{
    "email": "",
    "name" : "",
    "phone" : "",
    "subject" :" ",
    "message": ""
}

