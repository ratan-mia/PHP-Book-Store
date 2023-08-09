PHP Bookstore Sales Application

The PHP Bookstore Sales Application allows users to manage and view sales records for a bookstore. It has features to import data, filter records based on specific criteria, and see the total price of filtered entries.

Features
Data Import: Import sales data with a single click.
Filter Sales Records: View sales records based on customer name, product title, or maximum product price.
Total Price Calculation: Automatically calculates and displays the total price of the filtered sales entries.

Setup & Installation
Prerequisites:

Web Server (e.g., Apache, Nginx)
PHP (7+ recommended)
MySQL Database

Installation Steps:

Clone the repository to your web server's root directory.
Navigate to the project directory and update the db_connection.php file with your database credentials.
Import the provided SQL file (if any) to set up the database and tables.
Navigate to the application in your web browser to start using it.
Usage

Importing Data:

Click on the 'Import Data' button to import new sales records.
Filtering Sales Records:

Use the dropdown menus and input fields to specify the filter criteria.
Click the 'Filter' button to apply the filter and view the results.
Viewing Total Price:

The total price of the currently displayed sales records is shown at the bottom of the table.

Troubleshooting
Ensure that the database connection details are correctly specified in db_connection.php.
If encountering issues while filtering, check the error console or the response from the server for more details.

Contributing
Fork the repository.
Create a new branch for your feature or bugfix.
Commit your changes and open a pull request.
After review, your changes will be merged.

License
This project is licensed under the MIT License
