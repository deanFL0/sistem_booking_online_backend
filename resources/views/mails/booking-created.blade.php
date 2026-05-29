<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 5px 0 0 0; font-size: 14px; opacity: 0.9; }
        .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
        .booking-code { background: #f0f0f0; padding: 10px 15px; border-radius: 4px; font-family: monospace; font-size: 14px; font-weight: bold; margin: 15px 0; text-align: center; }
        .section-title { font-size: 16px; font-weight: bold; color: #667eea; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; color: #555; }
        .detail-value { color: #333; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; margin: 20px 0; text-align: center; font-weight: bold; }
        .button:hover { opacity: 0.9; }
        .footer { text-align: center; color: #888; font-size: 12px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
        .note { background: #fffbea; padding: 15px; border-left: 4px solid #ffc107; border-radius: 4px; margin: 15px 0; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Booking Confirmed!</h1>
            <p>Your reservation has been successfully booked</p>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $booking->customer_name }}</strong>,</p>
            
            <p>Thank you for your booking! We're excited to serve you. Your reservation has been confirmed and we've prepared everything for your visit.</p>
            
            <div class="booking-code">
                Booking Reference: {{ $booking->booking_code }}
            </div>
            
            <!-- Booking Details -->
            <div class="section-title">📅 Booking Details</div>
            
            <div class="detail-row">
                <span class="detail-label">Service:</span>
                <span class="detail-value">{{ $booking->service->name ?? 'N/A' }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ $booking->start_datetime->format('l, F j, Y') }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value">{{ $booking->start_datetime->format('g:i A') }} - {{ $booking->end_datetime->format('g:i A') }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Duration:</span>
                <span class="detail-value">{{ $booking->duration_minutes }} minutes</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Total Price:</span>
                <span class="detail-value"><strong>Rp.{{ number_format($booking->total_price, 2) }}</strong></span>
            </div>
            
            <!-- Customer Information -->
            <div class="section-title">👤 Your Information</div>
            
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value">{{ $booking->customer_name }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $booking->customer_email }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value">{{ $booking->customer_phone }}</span>
            </div>
            
            <!-- Important Notes -->
            <div class="note">
                <strong>⏰ Please arrive 5-10 minutes early</strong> to allow time for check-in and any final preparations.
            </div>
            
            <!-- Call-to-Action -->
            @if($booking->user_id)
            <center>
                <a href="http://localhost:5173/my-bookings/{{ $booking->id }}" class="button">
                    View or Manage Your Booking
                </a>
            </center>
            @else
            <center>
                <a href="http://localhost:5173/my-bookings/guest/{{ $booking->manage_token }}" class="button">
                    View or Manage Your Booking
                </a>
            </center>
            @endif
            
            <!-- Support Section -->
            <div class="section-title">❓ Need Help?</div>
            <p>If you need to reschedule, cancel, or have any questions about your booking, you can manage it directly through the link above. For urgent matters, please contact us at:</p>
            <ul>
                <li><strong>Email:</strong> support@bookingservice.com</li>
                <li><strong>Phone:</strong> +1 (555) 123-4567</li>
                <li><strong>Hours:</strong> Monday - Friday, 9:00 AM - 6:00 PM</li>
            </ul>
            
            <p>We look forward to seeing you soon!</p>
            
            <p><strong>Best regards,</strong><br>The Booking Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; 2026 Booking Service. All rights reserved.</p>
        </div>
    </div>
</body>
</html>