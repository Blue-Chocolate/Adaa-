API DOCUMENTATION 


post api/register

{
"name":"hassan",
  "email": "hassan2@example.com",
  "phone": "01000000001",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "admin",
  "bio": "Admin user for the platform.",
  "avatar": null
}

post api/login 

{
    "email": "hassan2@example.com",
    "password": "password123"
}



post api/orgnizations

{ "name": "TechCorp",
  "sector": "IT",
  "established_at": "2020-05-01",
  "email": "info@techcorp.com",
  "phone": "1234567890",
  "address": "123 Main St",
  "license_number": "LIC12345",
  "executive_name": "John Doe"
  }


  🧩 Shield Axes & Responses
Public (No Auth)
Action	HTTP	URL
Get all shield axes	GET	/api/shield-axes
Get specific axis	GET	/api/shield-axes/{axisId}
Authenticated Routes
Action	HTTP	URL
Get all axes	GET	/api/axes
Get one axis	GET	/api/axes/{axisId}
Get org shield status	GET	/api/organizations/{orgId}/shield-status
Get org axis responses	GET	/api/organizations/{orgId}/axes/{axisId}
Save bulk answers	POST	/api/organizations/{orgId}/axes/{axisId}
Upload attachment	POST	/api/organizations/{orgId}/axes/{axisId}/attachment
Delete attachment	DELETE	/api/organizations/{orgId}/axes/{axisId}/attachment/{attachmentNumber}
🎙 Podcasts
Action	HTTP	URL
List all podcasts	GET	/api/podcasts
Show podcast	GET	/api/podcasts/{id}
🚀 Releases
Action	HTTP	URL
Get all releases	GET	/api/releases
Get release	GET	/api/releases/{id}
Download release	GET	/api/releases/{id}/download
📝 Blogs
Action	HTTP	URL
List all blogs	GET	/api/blogs
Show blog	GET	/api/blogs/{id}
🛡 Shield Module
Public
Action	HTTP	URL
Get analytics	GET	/api/shield/analytics
Get shield organizations	GET	/api/shield/organizations
Authenticated
Action	HTTP	URL
Get questions & answers	GET	/api/shield/questions
Submit answers	POST	/api/shield/submit
Upload attachment	POST	/api/shield/attachment/upload
Download results	GET	/api/shield/download-results
