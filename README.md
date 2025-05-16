Instructions to Run the RMS:

Please make sure PHP is installed before running the RMS

PHP download and set up:
1. Watch this 1 minute video to Download and Set up Php on your computer
https://www.youtube.com/watch?v=n04w2SzGr_U

2. Open the folder where php has been extracted e.g( C:\php)
3. Inside the extracted folder, locate `php.ini-development` and rename it to `php.ini`

## Enable the MySQL PDO Driver

1. Open `C:\php\php.ini` in a text editor
2. Find this line:
   ```
   ;extension=pdo_mysql
   ```
3. Remove the semicolon to enable it:
   ```
   extension=pdo_mysql
   ```
4. Save the file.
   

Download and extract the the WUC-RMS Zip file
Open the the WUC RMS folder in file explorer
Open the Public Folder
Right Click and Open Terminal (while in the public folder)
Run the Command: php -S localhost:8000
In the web-browser Search for http://localhost:8000


Admin Login Details:
Username: 3041
Password: test123
