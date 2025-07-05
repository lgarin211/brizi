# Midtrans Raw Body Testing Guide

## Testing Raw Body Parameter Extraction

Use these endpoints to test and troubleshoot order_id extraction from various data formats:

### 1. Test Raw Body Endpoint
```bash
# Test with JSON body
curl -X POST http://localhost:8000/api/midtrans/test-raw-body \
  -H "Content-Type: application/json" \
  -d '{"order_id": "PLAZA-123-1673456789", "status": "success"}'

# Test with form data
curl -X POST http://localhost:8000/api/midtrans/test-raw-body \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "order_id=PLAZA-123-1673456789&status=success"

# Test with query parameters
curl -X GET "http://localhost:8000/api/midtrans/test-raw-body?order_id=PLAZA-123-1673456789&status=success"

# Test with mixed data (query + body)
curl -X POST "http://localhost:8000/api/midtrans/test-raw-body?method=test" \
  -H "Content-Type: application/json" \
  -d '{"order_id": "PLAZA-123-1673456789"}'
```

### 2. Debug Endpoint
```bash
# Debug any callback data
curl -X POST http://localhost:8000/api/midtrans/debug \
  -H "Content-Type: application/json" \
  -d '{"order_id": "TEST-123", "transaction_status": "settlement"}'

# Debug form data
curl -X POST http://localhost:8000/api/midtrans/debug \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "order_id=TEST-123&transaction_status=settlement"
```

### 3. Testing Midtrans Finish Callbacks

The finish/unfinish/error endpoints now accept both GET and POST methods:

```bash
# Test finish callback with GET
curl -X GET "http://localhost:8000/api/midtrans/finish?order_id=PLAZA-123-1673456789"

# Test finish callback with POST JSON
curl -X POST http://localhost:8000/api/midtrans/finish \
  -H "Content-Type: application/json" \
  -d '{"order_id": "PLAZA-123-1673456789"}'

# Test finish callback with POST form
curl -X POST http://localhost:8000/api/midtrans/finish \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "order_id=PLAZA-123-1673456789"
```

## Order ID Parameter Variations

The system now checks for these parameter names:
- `order_id`
- `ORDER_ID`
- `orderId`
- `orderid`
- `ORDERID`
- `transaction_id`
- `TRANSACTION_ID`
- `transactionId`
- `id`
- `ID`

## Raw Body Processing

The middleware processes data in this order:
1. Store raw body content
2. Parse JSON if Content-Type indicates JSON
3. Parse form data if Content-Type indicates form or if no data exists
4. Parse query strings in raw body
5. Merge all parsed data into Laravel request object

## Troubleshooting

If order_id is still not found:

1. **Check the debug endpoint** to see exactly what data is being received
2. **Check Laravel logs** for detailed extraction attempts
3. **Verify Content-Type headers** from Midtrans
4. **Test with the test-raw-body endpoint** to verify extraction logic

## Common Issues and Solutions

### Issue: "Payment process completed but order ID not found"
**Solutions:**
1. Check Midtrans Dashboard callback URL configuration
2. Use `/api/midtrans/debug` to see actual parameters sent
3. Verify parameter names match expected variations
4. Check that middleware is applied to routes

### Issue: Raw body is empty
**Solutions:**
1. Verify Midtrans sends POST data in callback
2. Check if Laravel is consuming input stream elsewhere
3. Ensure middleware runs before other input processing

### Issue: JSON parsing fails
**Solutions:**
1. Check Content-Type header from Midtrans
2. Verify JSON is valid using debug endpoint
3. Check for BOM or encoding issues

## Middleware Configuration

Ensure `MidtransCallbackMiddleware` is applied to all callback routes:
- `/api/midtrans/notification`
- `/api/midtrans/finish`
- `/api/midtrans/unfinish`
- `/api/midtrans/error`
- `/api/midtrans/success`
- `/api/midtrans/failed`
- `/api/midtrans/debug`
- `/api/midtrans/test-raw-body`
