# uber-project

The premise of this project is simple:

What happens when I combine my Google Calendar with the Uber API?


This project is still super ugly.

In short, you have to:

*  get a valid set of credentials using Google's OAuth (1 hour expiration);
*  get a valid set of credentials using Uber's OAuth (30 day expiration);
*  choose which calendars you actually want to import;
*  set your home address, as everything uses that for estimates and ETAs.

## TODO

*  Use the Google library to do the map and geocoding lookups. Right now it's raw Guzzle.
*  Wrap the Uber API calls into a real library. It too is raw Guzzle.
*  Make it more interactive:
 *  Allow the user to set their home address;
 *  Allow the user to choose which calendars to import;
 *  Allow the user to accept/decline reservations and requests;
*  Raze it and start over fresh: the entire freakin' script is brute force. With a little more thought, this could be more useful.