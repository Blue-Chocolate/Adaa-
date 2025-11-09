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