#QUICK INSTALL

For the impatient, here is a basic outline of the
installation process, which normally takes me only
a few minutes:

1. Move the Moodle files into your web directory.

1. Create a single database for Moodle to store all
   its tables in (or choose an existing database).

1. Visit your Moodle site with a browser, you should
   be taken to the install.php script, which will lead
   you through creating a config.php file and then
   setting up Moodle, creating an admin account etc.

1. Set up a cron task to call the file admin/cron.php
   every five minutes or so.


For more information, see the INSTALL DOCUMENTATION:

   http://docs.moodle.org/en/Installing_Moodle


Good luck and have fun!
Martin Dougiamas, Lead Developer

#Configuration Notes for WBLMS

##Customize Management Block
Add an HTML block titled "Management" to contain the links to the custom modules with the following HTML:
    <ul>
      <li>
        <p><a href="http://wblms.westbayri.org/mod/recertpol/view.php"><span style="font-size: medium; color: #808080;">Manage recertification policies</span></a></p>
      </li>
      <li>
        <p><a href="http://wblms.westbayri.org/enrol/staff/view.php?id=1"><span style="font-size: medium; color: #808080;">Manage staff enrollments</span></a></p>
      </li>
      <li>
        <p><a href="http://wblms.westbayri.org/admin/undel_user.php"><span style="font-size: medium; color: #808080;">Undelete staff records</span></a></p>
      </li>
    </ul>

