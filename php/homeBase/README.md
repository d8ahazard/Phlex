# Homebase (for Plex)
If you run a Plex Media Server that you share with users outside of your home, Homebase is a simple webpage that can easily direct your users to services such as Ombi to request new content and it can present ~~useless~~ useful and ~~boring~~ fun statistics about your media server. As a bonus, there is included a matching template for the Tautulli Newsletter.

![Main Image](https://i.imgur.com/4qMQrhs.png)

## Demo - [Link](https://app-1536693495.000webhostapp.com/)

## Features:
- Plex online status
- Current server activity
- Plex library totals organized by library type (show, movie, artist)
- Monthly active users
- Top Content Ratings, Top Genres, Top Platforms, and Most Popular TV Shows & Movies
- Custom "Recently Added" template for Tautulli HTML Newsletter

## Requirements:
- [Plex Media Server](https://www.plex.tv/)
- [Tautulli](https://tautulli.com/)
- Webserver with PHP (requires cURL module)
- A general grasp of HTML and CSS to do any sort of customization

## Installation & Setup:
- Download project files
- Fill in PLEX_URL and PLEX_TOKEN in `plex-api.php`
- Fill in TAUTULLI_URL and TAUTULLI_TOKEN in `tautulli-api.php`
- Edit the menu links in `index.html` to your desire (starting on line 38)
- Edit the Request Content & Recently Added&#42; sections and their links as you see fit
- Upload the files to your webserver
- Enjoy!

&#42; In order to use the Recently Added template, you'll have to do a few things:
- Be sure to edit the menu links in `recently_added.html` as you had in `index.html` 
- Put the `recently_added.html` file into a folder accessible by Tautulli and then enter the path to this file under your Tautulli Settings: `Settings > Notifications & Newsletters > Custom Newsletter Templates Folder` (you'll need to click "Show Advanced" to see this setting)
- You'll then want to enter a publicly accessible folder under "Newsletter Output Directory" (personally, I am just using the /recentlyadded directory since I'm hosting these files on the same server that Tautulli is running)
- Next, add a "Newsletter Agent" and specifically make sure to click "Save HTML File Only" under "Saving & Sending" and it would be advised to enter `index.html` under "HTML File Name"

## Theming:
Homebase is currently themed with Plex's official color scheme but if you wish to change it, feel free to modify custom.css / custom.min.css.

## Issues:
- The current functions to get the Top Genres and Top Content Ratings currently make as many calls to Plex as there are genres and content ratings, which can be a very large number in many cases. This could certainly be made more efficient.

## Want to help out?
This whole project is very much an experiment for me so I am definitely open to suggestions to improve any aspect of this code. The more I've been working on this, the more apparent it is I should lean more on PHP to collect/sort these statistics and implement a database of some kind to store everything (particularly Top Genres, Content Ratings, Writers, Directors, Actors, etc). I may still do that but it would undoubtedly require a complete rewrite of what's here, so don't expect me commit anything like that to this repo.

## Troubleshooting
If you're experiencing issues, please feel free to submit an issue and I'll do my best to help you solve it!

## License
Licensed under The MIT License. Not affiliated with Plex Inc or Tautulli.
