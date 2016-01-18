# Course Archiver
This tool is used to search for courses, notify the course owners, and mass hide or archive (backup and remove) courses.
The courses are stored with Moodle's backup file extension ".mbz".  These files can then be manually found in the user defined folder and can be restored using Moodle's built in restore feature.

## Install
copy the coursearchiver folder into the admin/tool folder.

## Settings
#### Folder path
A folder created within the moodledata folder.  The tool will create this folder, however it is recommended that the folder be created outside the moodledata folder, and a shortcut be placed in the moodledata folder.

#### Course Hide Email
This is the template email that will be sent to the owners of the selected courses to notify them that their course(s) will be hidden.  There are two required variables in the email %to (name of the recipient) and %courses (a list of courses with mailto hyperlinks to notify the site administrator that the user wishes to opt out)

#### Course Archive Email
This is the template email that will be sent to the owners of the selected courses to notify them that their course(s) will be archived.  There are two required variables in the email %to (name of the recipient) and %courses (a list of courses with mailto hyperlinks to notify the site administrator that the user wishes to opt out)