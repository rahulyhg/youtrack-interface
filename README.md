#### usage
##### time tracker
Pasting a full ticket reference into the search box when 'select  a project' is selected in the drop down, shows the reference in the results drop-down. note: it dosn't check if the reference is valid.
Pasting a full ticket reference into the ticket no. box, sets the the full ticket reference.

#### installation
* composer install 
* make sure the 'timings', 'cache', 'uploads' and 'export' folders have apache/nginx read & write permissions
* fill in the details in customSettings.php, its important change the cookie encryption key for security reasons.
* aim you web site url at the htdocs folder

#### notes
Custom fields may not have a ¬ in the name for custom fields form to work.

Time tracker keeps a copy of all form edits on the server, to stop browser cache clearence removing info. Each time a section of the form is updated a new copy of the information is saved. 

#### requirements
Composer
PHP5+