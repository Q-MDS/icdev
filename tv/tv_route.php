1. do the includes which presents the login
2. $user = getenv(“REMOTE_USER”); 
3. select branch from user_Details where is_current=’Y’ and username=’$user’ 
4. Get list of tvs from DEPARTURE_TVS where branch=’$branch’
5. Draw layout
   - Heasding: Departure TVs
   - grid - 2 columns
	 - 1st column: rectangle with tv code in center
	 - 2nd column: Select route dropdown - Row 1
	 - 2nd column: Add/remove button - Row 2 (if empty button is disabled, if has a route the active and ADD, if has route the active and REMOVE )
	   - read DEPARTURE_TV_SETTINGS for the tv code and route - if no record found button is disabled, user selects route from dropdown, button becomes enabled and ADD
	   - read DEPARTURE_TV_SETTINGS for the tv code and route - if record IS found, set route from data and button is enabled and called REMOVE