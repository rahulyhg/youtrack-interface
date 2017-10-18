# Youtrack Interface
"A simple to use web based interface for speeding up tasks for youtrack"

included
- time tracker
- csv ticket import
- web page form for creating multiple tickets
- cache

#### requirements
- Composer
- PHP5+

#### installation
* composer install 
* make sure the 'timings', 'cache', 'uploads' and 'export' folders have apache/nginx read & write permissions
* fill in the details in customSettings.php, its important change the cookie encryption key for security reasons. The youtrack url is generally in the format https://******.myjetbrains.com/youtrack.
* aim you web site url at the htdocs folder

#### csv ticket importer
The csv ticket importer relies on the youtrack api, as does the rest of this. The Importer can be quite precise about what it can accept.

#### ticket importer from page
This page works alot nicer than the csv importer. This is because the different types of fields are shown as dropdowns etc and are submitted to yourack in the required format.

#### time tracker
Track your time on your machine against a ticket reference, using a start/stop button and submit the time logged for this ticket when you are ready.

Now includes searching for a ticket in a project by a given string.

##### notes
Pasting a full ticket reference into the search box when 'select  a project' is selected in the drop down, shows the reference in the results drop-down. note: it dosn't check if the reference is valid.
Pasting a full ticket reference into the ticket no. box, sets the the full ticket reference.

#### know issues
##### Custom fields Naming
Custom fields may not have a ¬ in the name for custom fields form to work.

Time tracker keeps a copy of all form edits on the server, to stop browser cache clearence removing info. Each time a section of the form is updated a new copy of the information is saved. 

##### ssl cirtificate issue
```PHP Fatal error:  Uncaught exception 'Guzzle\\Http\\Exception\\CurlException' with message '[curl] 60: SSL certificate problem: unable to get local issuer certificate [url] ...```

This is an issue that has arrisen a few times on our system and to do with ssl cirtificate not being found and is to do with the server configuration this program my program is held. This seems to be a common issue with curl and is to do with the way curl works.
