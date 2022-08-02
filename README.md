Mcopyfind
Mcopyfind is a Moodle plagiarism plugin based on wcopyfind/copyfind which aims to provide a usable tool to detect collusion.

Setup to start developing
To start developing you need a running moodle instance. If you use windows you can Download pre packaged <a href="https://docs.moodle.org/311/en/Windows_installation">windows moodle</a>.
With the windows version we change the opcache settings to speed up the refreshrate of our php files. Change cache settings to be able to develop more comfortably by disabling the opcache in the php.ini
At the bottom of the file change the settings to the following:
```
# Moodle4Windows customizations  

;opcache.enable=1  

;Custom Development settings  
opcache.validate_timestamps=on  
opcache.revalidate_freq=0
```

Or use the instructions for a <a href="https://docs.moodle.org/311/en/Windows_installation_using_Git">windows based </a> git installation: Version used: Moodle 3.9.12+ (Build: 20220226)
If you are using Linux use the <a href="https://docs.moodle.org/39/en/Installing_Moodle">official guide</a>.

If you want to contribute to the Plugin development the learnMoodle <a href="https://learn.moodle.org/course/view.php?id=26428"> Plugin developer couse </a> is recommended, or better the newer <a href="https://moodle.academy/course/view.php?id=64">academy course</a>.
The Sql client used was <a href="https://www.heidisql.com/download.php">heidisql</a>.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/plagiarism/mcopyfind

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2022 Johannes Wanner <johannes.wanner@web.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
