# Midtrans Integration Setup Guide

## Plaza Facility Booking - Midtrans Sandbox Integration

This guide explains how to set up Midtrans Sandbox for payment processing in the Plaza Facility Booking system.

## 1. Midtrans Account Setup

1. **Create Sandbox Account**: Go to [Midtrans Sandbox](https://dashboard.sandbox.midtrans.com/)
2. **Get API Keys**: 
   - Login to dashboard
   - Go to Settings > Access Keys
   - Copy Server Key and Client Key

## 2. Environment Configuration

Add these variables to your `.env` file:

```env
# Midtrans Sandbox Configuration
MIDTRANS_SERVER_KEY=SB-Mid-server-YOUR_SERVER_KEY_HERE
MIDTRANS_CLIENT_KEY=SB-Mid-client-YOUR_CLIENT_KEY_HERE
MIDTRANS_IS_PRODUCTION=false
```

## 3. Database Migration

Run the migration to add Midtrans fields:

```bash
php artisan migrate
```

This adds the following fields to `transaction` table:
- `order_id`: Unique order identifier
- `payment_type`: Selected payment method
- `snap_token`: Token from Midtrans Snap API
- `snap_redirect_url`: Redirect URL for payment
- `midtrans_response`: Full response from Midtrans

## 4. Notification URL Setup

In your Midtrans Dashboard:

1. Go to Settings > Configuration
2. Set Notification URL to: `https://yourdomain.com/api/midtrans/notification`
3. Set Redirect URL to: `https://yourdomain.com/api/midtrans/finish`

## 5. API Endpoints

### Create Payment
```http
POST /api/midtrans/create-payment
```

**Request Body:**
```json
{
    "transaction_id": 123,
    "payment_type": "credit_card",
    "customer_details": {
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "08123456789",
        "address": "Jl. Sudirman No. 1",
        "city": "Jakarta",
        "postal_code": "12190"
    }
}
```

**Response:**
```json
{
    "success": true,
    "order_id": "PLAZA-123-1673456789",
    "snap_token": "66e4fa55-xxxx-xxxx-xxxx-c632bc0c8bac",
    "snap_redirect_url": "https://app.sandbox.midtrans.com/snap/v2/vtweb/...",
    "expires_at": "2025-07-06T10:30:00Z"
}
```

### Check Payment Status
```http
GET /api/payment-status?order_id=PLAZA-123-1673456789
```

## 6. Frontend Integration Options

### Option 1: Snap.js (Recommended)
```html
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="YOUR_CLIENT_KEY"></script>
<script>
snap.pay('SNAP_TOKEN', {
    onSuccess: function(result) {
        // Payment success
        window.location.href = '/booking-success';
    },
    onPending: function(result) {
        // Payment pending
        console.log(result);
    },
    onError: function(result) {
        // Payment error
        console.log(result);
    }
});
</script>
```

### Option 2: Redirect
Redirect user directly to `snap_redirect_url` from API response.

## 7. Payment Methods Available

- **Credit Card**: Visa, MasterCard, JCB
- **Bank Transfer**: BCA, BNI, BRI, Mandiri, Permata
- **E-Wallet**: GoPay, ShopeePay, DANA, OVO, LinkAja
- **Convenience Store**: Indomaret, Alfamart
- **QRIS**: Universal QR Code

## 8. Testing

### Test Credit Cards (Sandbox)
- **Success**: 4811 1111 1111 1114
- **Failure**: 4911 1111 1111 1113
- **CVV**: 123
- **Expiry**: Any future date

### Test Bank Transfer
Use any amount, transaction will be auto-accepted in sandbox.

### Test E-Wallet
- GoPay: Use phone number 081234567890
- ShopeePay: Use any valid phone number

## 9. Production Deployment

1. Change `MIDTRANS_IS_PRODUCTION=true`
2. Update API keys with production keys
3. Update notification URL to production domain
4. Test with small amounts first

## 10. Security Notes

- ✅ Server key validation implemented
- ✅ Signature verification for notifications
- ✅ HTTPS required for production
- ✅ Proper error logging
- ✅ Transaction expiry handling

## 11. Troubleshooting

### Common Issues:
1. **Invalid signature**: Check server key configuration
2. **Transaction not found**: Verify order_id format
3. **API timeout**: Check network connectivity
4. **Invalid amount**: Ensure amount is integer (no decimals)

### Logs Location:
- Laravel logs: `storage/logs/laravel.log`
- Midtrans logs: Check Laravel log with tag 'Midtrans'

## 8. Raw Body Data Handling

The system now supports receiving data via raw body from Midtrans callbacks. This is handled automatically by:

### Middleware Processing

- `MidtransCallbackMiddleware` processes all raw body data
- Supports JSON and form-encoded data in raw body
- Automatically parses and merges data with Laravel request object

### Order ID Extraction

The system tries to extract `order_id` from multiple sources:

1. Regular request parameters (`order_id`, `ORDER_ID`)
2. Query parameters
3. JSON data in raw body
4. Form data in raw body
5. URL-encoded strings in raw body

### Debug Endpoint

Use `/api/midtrans/debug` to troubleshoot callback parameter issues:

```bash
# Test with different data formats
curl -X POST https://yourdomain.com/api/midtrans/debug \
  -H "Content-Type: application/json" \
  -d '{"order_id": "PLAZA-123-1673456789"}'

curl -X POST https://yourdomain.com/api/midtrans/debug \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "order_id=PLAZA-123-1673456789"
```

### CSRF Protection

Midtrans callback routes are excluded from CSRF verification:

- All `/api/midtrans/*` routes are exempt from CSRF tokens
- Raw body data is preserved and processed correctly

## Documentation References

- [Midtrans Snap API](https://docs.midtrans.com/reference/backend-integration)
- [Midtrans Notification](https://docs.midtrans.com/docs/http-notification-webhooks)
- [Payment Methods](https://docs.midtrans.com/docs/payment-methods)
