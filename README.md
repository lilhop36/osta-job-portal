# OSTA Job Portal

A comprehensive recruitment management system designed to streamline the job application and hiring process for the Oromia Science and Technology Authority (OSTA).

## 🚀 Features

### For Job Seekers
- Centralized application system
- Profile management
- Document upload and verification
- Application tracking
- Job search and alerts
- Interview scheduling

### For Employers
- Job posting and management
- Applicant tracking
- Interview management
- Reports and analytics
- Document verification

### For Administrators
- User management
- System configuration
- Comprehensive reporting
- Security management
- System monitoring

## 👨‍💻 About Me

**Abdi GR**  
Lead Developer & Project Maintainer  

I'm a passionate developer dedicated to creating efficient and user-friendly web applications. This project was developed to modernize the recruitment process for the Organization of Southern Trade Associations (OSTA).

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Web Server**: Apache/Nginx
- **Security**: CSRF protection, XSS prevention, secure session management

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- Composer (for dependency management)
- Git (for version control)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/lilhop36/osta-job-portal.git
   cd osta-job-portal
   ```

2. **Set up the database**
   - Import the database schema from `database/` directory
   - Configure database credentials in `config/database.php`

3. **Configure the application**
   - Copy `.env.example` to `.env` and update with your configuration
   - Set up email settings in `config/email.php`

4. **Set file permissions**
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 assets/
   ```

5. **Access the application**
   - Open your browser and navigate to your web server's URL
   - Complete the installation wizard if this is the first run

## 🔒 Security

This application includes several security features:
- CSRF protection
- XSS prevention
- Secure password hashing
- Input validation and sanitization
- Role-based access control
- Secure session management

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📧 Contact

For support or queries, please contact the development team.

---

*This project was developed for the Organization of Southern Trade Associations (OSTA) to modernize their recruitment processes.*
