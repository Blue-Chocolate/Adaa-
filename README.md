# Complete API Documentation

## Table of Contents
- [Test Data Setup](#test-data-setup)
- [Authentication](#authentication)
- [Organizations](#organizations-crud)
- [Shield Module](#shield-module)
- [Podcasts](#podcasts)
- [Releases](#releases)
- [Blogs](#blogs)

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
  "answers": [
    {
      "question_id": 1,
      "answer": true,
      "attachment": null
    },
    {
      "question_id": 2,
      "answer": null,
      "attachment": null
    }
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
  - Key: `file`
  - Value: Select file (PDF, DOCX, JPG, PNG, etc.)

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
    },
    {
      "question_id": 3,
      "answer": true,
      "attachment": null
    },
    {
      "question_id": 4,
      "answer": true,
      "attachment": null
    }
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
```bash
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
  "answers": [
    {"question_id": 1, "answer": true, "attachment": null},
    {"question_id": 2, "answer": false, "attachment": null},
    {"question_id": 3, "answer": true, "attachment": null},
    {"question_id": 4, "answer": true, "attachment": null}
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

## Authentication

All protected endpoints require authentication using Laravel Sanctum. Include the authentication token in request headers.

```
Authorization: Bearer {token}
```

### Register
**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
  "name": "Hassan Ali",
  "email": "hassan@example.com",
  "phone": "01000000001",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "admin",
  "bio": "Admin user for the platform",
  "avatar": null
}
```

### Login
**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "hassan@example.com",
  "password": "password123"
}
```

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
```

---

## Frontend Integration Tips

### Axios Example (React/Vue)

```javascript
// Setup axios instance
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://your-domain.com/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Login
async function login(email, password) {
  const response = await api.post('/login', { email, password });
  localStorage.setItem('token', response.data.token);
  return response.data;
}

// Get questions
async function getQuestions() {
  const response = await api.get('/shield/questions');
  return response.data.axes;
}

// Save draft
async function saveDraft(answers) {
  const response = await api.post('/shield/save', { answers });
  return response.data;
}

// Submit final
async function submitAnswers(answers) {
  const response = await api.post('/shield/submit', { answers });
  return response.data;
}

// Upload file
async function uploadFile(file) {
  const formData = new FormData();
  formData.append('file', file);
  
  const response = await api.post('/shield/attachment/upload', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  });
  
  return response.data.file_url;
}
```

### Fetch API Example (Vanilla JS)

```javascript
const BASE_URL = 'http://your-domain.com/api';
let authToken = null;

// Login
async function login(email, password) {
  const response = await fetch(`${BASE_URL}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  authToken = data.token;
  localStorage.setItem('token', authToken);
  return data;
}

// Get questions
async function getQuestions() {
  const response = await fetch(`${BASE_URL}/shield/questions`, {
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}

// Submit answers
async function submitAnswers(answers) {
  const response = await fetch(`${BASE_URL}/shield/submit`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ answers })
  });
  
  return await response.json();
}
```

---

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