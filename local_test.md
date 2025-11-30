To run this project locally, you'll need MySQL and a PHP web server. Here's how:

  1. Database Setup

  # Connect to MySQL
  mysql -u root -p

  # Run SQL files in order
  source /database/sql/01_create_database.sql
  source /database/sql/02_create_views.sql
  source /database/sql/03_create_procedures.sql
  source /database/sql/04_advanced_queries.sql
  source /database/sql/05_seed_data.sql

  2. Configure PHP Database Connection

  Edit php/config/database.php with your MySQL credentials:
  $this->host = 'localhost';
  $this->db_name = 'summitsphere';
  $this->username = 'your_username';
  $this->password = 'your_password';

  3. Start PHP Development Server

  cd /database/php
  php -S localhost:8000

  Then open http://localhost:8000 in your browser.

  4. Demo Logins

  | Role     | Username           | Password    |
  |----------|--------------------|-------------|
  | Manager  | thomas.anderson    | password123 |
  | Staff    | emily.parker       | password123 |
  | Supplier | supplier.cyclegear | password123 |
  | Customer | john.smith         | password123 |

  ---
  Prerequisites check - Do you have MySQL and PHP installed? I can help verify:

  mysql --version
  php --version

  Would you like me to run these checks or help with any specific step?