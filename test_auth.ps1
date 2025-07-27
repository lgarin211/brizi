# PowerShell script untuk test authentication endpoints
$baseUrl = "http://localhost:8000/api/auth"

Write-Host "=== Testing PlazaFest Authentication API ===" -ForegroundColor Green
Write-Host ""

# Test 1: Register
Write-Host "1. Testing Registration..." -ForegroundColor Yellow
$registerData = @{
    first_name            = "John"
    last_name             = "Doe"
    email                 = "john.test@example.com"
    phone                 = "081234567890"
    password              = "password123"
    password_confirmation = "password123"
    city                  = "Jakarta"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/register" -Method Post -Body $registerData -ContentType "application/json"
    Write-Host "Register Success:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 10
    $apiToken = $response.data.api_token
}
catch {
    Write-Host "Register Error:" -ForegroundColor Red
    $_.Exception.Message
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.BaseStream.Position = 0
        $reader.DiscardBufferedData()
        $responseBody = $reader.ReadToEnd()
        Write-Host $responseBody
    }
}

Write-Host ""

# Test 2: Login
Write-Host "2. Testing Login..." -ForegroundColor Yellow
$loginData = @{
    email    = "john.test@example.com"
    password = "password123"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $loginData -ContentType "application/json"
    Write-Host "Login Success:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 10
    if (-not $apiToken) {
        $apiToken = $response.data.api_token
    }
}
catch {
    Write-Host "Login Error:" -ForegroundColor Red
    $_.Exception.Message
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $reader.BaseStream.Position = 0
        $reader.DiscardBufferedData()
        $responseBody = $reader.ReadToEnd()
        Write-Host $responseBody
    }
}

Write-Host ""

# Test 3: Get Profile (if we have token)
if ($apiToken) {
    Write-Host "3. Testing Get Profile..." -ForegroundColor Yellow
    try {
        $headers = @{
            "Authorization" = "Bearer $apiToken"
        }
        $response = Invoke-RestMethod -Uri "$baseUrl/profile" -Method Get -Headers $headers
        Write-Host "Profile Success:" -ForegroundColor Green
        $response | ConvertTo-Json -Depth 10
    }
    catch {
        Write-Host "Profile Error:" -ForegroundColor Red
        $_.Exception.Message
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $reader.BaseStream.Position = 0
            $reader.DiscardBufferedData()
            $responseBody = $reader.ReadToEnd()
            Write-Host $responseBody
        }
    }
}

Write-Host ""
Write-Host "=== Testing Completed ===" -ForegroundColor Green
