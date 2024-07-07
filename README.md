# MTG-API

- [Swagger](https://app.swaggerhub.com/apis-docs/DEVBATISTETI/mtg-api/1.0.0)
- / : overview endpoint, briefly explaining the project
- /user/create : creates an user and provides an API key/token
- /user/changePassword/{id} : changes the current user password... requires authentication and most likely a landing page implementation to work properly
- /user/resetPassword : currently, revokes given token... should work vest with a landing page as well
- /auth/login : receives login credentials and returns a new API key/token
- /auth/logoff : revokes given token
- /sets/list : list all sets by given parameters, no authentication required
- /sets/create : create a new set, authentication required
- /sets/update/{id} : update provided set, authentication required
- /sets/delete/{id} : delete provided set, authentication required
- /cards/list : list all cards by given parameters, no authentication required
- /cards/create : create a new card, authentication required
- /cards/update/{id} : update provided card, authentication required
- /cards/delete/{id} : delete provided card, authentication required

I used some stuff from [Scryfall](https://scryfall.com)

Main endpoint is [http://localhost/mtg-api/api.php](http://localhost/mtg-api/api.php) (this might change depending on your cloning)