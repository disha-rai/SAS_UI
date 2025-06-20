# from flask import Flask, request, jsonify
# from flask_cors import CORS
# import smtplib
# from email.mime.text import MIMEText
# import logging

# app = Flask(__name__)
# CORS(app)

# logging.basicConfig(level=logging.DEBUG, format='%(asctime)s - %(levelname)s - %(message)s')

# @app.route('/send-otp', methods=['POST'])
# def send_otp():
#     data = request.get_json()
#     email = data.get('email')
#     otp = data.get('otp')  # Receive OTP from PHP

#     if not email or not otp:
#         app.logger.error('Email and OTP are required')
#         return jsonify({'status': 'error', 'message': 'Email and OTP are required'}), 400

#     # Send OTP via Gmail SMTP
#     try:
#         msg = MIMEText(f'Your OTP is: {otp}. It is valid for 10 minutes.')
#         msg['Subject'] = 'Smart Attendance System OTP'
#         msg['From'] = 'erm.foreg@gmail.com'
#         msg['To'] = email

#         server = smtplib.SMTP_SSL('smtp.gmail.com', 465)
#         server.login('erm.foreg@gmail.com', 'fnls zcbx igdw kfxk')
#         server.sendmail(msg['From'], msg['To'], msg.as_string())
#         server.quit()
        
#         app.logger.info(f'OTP {otp} sent to {email}')
#         return jsonify({'status': 'success', 'message': 'OTP sent'})
#     except Exception as e:
#         app.logger.error(f'Failed to send OTP to {email}: {str(e)}')
#         return jsonify({'status': 'error', 'message': f'Failed to send OTP: {str(e)}'}), 500

# if __name__ == '__main__':
#     app.run(host='0.0.0.0', port=5000, debug=True)