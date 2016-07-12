# plugin-photos
RedLine13 Plugin for Photos enabling quick creation for building photo load tests, currently cloud performance testing via S3 uploads.

RedLine13 Plugins enable users to modify and control cloud load test execution and test data.  They provide a simple way for teams to share and expand test execution.  To read more about building plugins check out http://www.redline13.com/blog/2016/07/building-directly-redline13-via-community-plugins

## files
* plugin_photos.sh - Just example plugin_install, nothing included
* plugin.html - includes markup and js for page.  This plugin does not require uploaded files.
* UploadTest.class.php - The custom test executing photo uploads.


## Photos Plugin 
The initial photos plugin is made available to support file uploads direct to S3. 
![PhotoUpload Test](https://www.redline13.com/blog/wp-content/uploads/2013/06/Start-a-Photo-Load-Test.png)

A set of images are dynamically generated at load test start, these settings control details of image.
* Width
* Height
* Format : Supports JPG, PNG, GIF

S3 Details
* Meta : Allows meta fields to be pushed with image into S3
* URL Endpoint : S3 Endpoint

S3 Security, settings are S3 Policy parameters to do direct upload.
* Credential
* Date
* Policy
* Signature
* Security Token

Test parameters - control test execution
* Photos to send - For each 'User' will send # Photos
* Length of time [not yet implemented] - Instead of photo count, send photos for N minutes, then stop test.
* Min Delay - After each photo wait at least min milliseconds
* Max Delay - After each photo wait at most max milliseconds

TODO: Open it up to generic photo uploads. 

Contact info@redline13.com for updates for this plugin.
