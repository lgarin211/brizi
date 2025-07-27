@echo off
echo Testing PlazaFest Authentication API...
echo.

set BASE_URL=http://localhost:8080/api/auth

echo 1. Testing Registration...
curl -X POST %BASE_URL%/register ^
  -H "Content-Type: application/json" ^
  -d "{\"first_name\":\"John\",\"last_name\":\"Doe\",\"email\":\"john.doe@test.com\",\"phone\":\"081234567890\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}"

echo.
echo.

echo 2. Testing Login...
curl -X POST %BASE_URL%/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"john.doe@test.com\",\"password\":\"password123\"}"

echo.
echo Testing completed.
pause
