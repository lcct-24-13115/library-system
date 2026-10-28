<?php

/*
|--------------------------------------------------------------------------
| Web Search: external library whitelist
|--------------------------------------------------------------------------
|
| Only libraries listed here can be opened from the Web Search button.
| The user never types a URL; they only pick a `key`, so nobody can use the
| feature to open an arbitrary website.
|
| Each entry:
|   key          slug used by the form (letters, numbers, dashes)
|   name         label shown in the dropdown
|   url_template https:// address. If it contains {query}, the search text
|                is URL-encoded and put there. If it does NOT contain
|                {query}, the library's page just opens (no search text).
|   verified     true  = the URL was checked in a browser
|                false = best guess, please verify before relying on it
|   note         optional remark for the librarian / developer
|
| To add a library later, copy one block and change the values.
|
*/

return [

    // key of the library that is selected first in the dropdown
    'default' => 'philippine-elib',

    'libraries' => [

        [
            'key' => 'philippine-elib',
            'name' => 'Philippine E-Lib',
            'url_template' => 'https://elib.gov.ph/bsearch.php',
            'verified' => true,
            'note' => 'Opens the Basic Search page (confirmed in the old system). '
                .'TODO: find out if it accepts a search text in the address; if yes, add ?...={query}.',
        ],

        [
            'key' => 'ateneo-de-manila',
            'name' => 'Ateneo De Manila University Library',
            'url_template' => 'https://rizal.ateneo.edu',
            'verified' => false,
            'note' => 'TODO: verify the address and the catalog search pattern.',
        ],

        [
            'key' => 'ateneo-de-naga',
            'name' => 'Ateneo De Naga University Library',
            'url_template' => 'https://www.adnu.edu.ph',
            'verified' => false,
            'note' => 'TODO: verify the library catalog address.',
        ],

        [
            'key' => 'anu',
            'name' => 'Australian National University Library',
            'url_template' => 'https://anulib.anu.edu.au',
            'verified' => false,
            'note' => 'TODO: verify the address and the catalog search pattern.',
        ],

        [
            'key' => 'irri',
            'name' => 'IRRI Library',
            'url_template' => 'https://www.irri.org',
            'verified' => false,
            'note' => 'TODO: verify the library catalog address.',
        ],

        [
            'key' => 'la-salle-taft',
            'name' => 'La Salle Taft Library',
            'url_template' => 'https://library.dlsu.edu.ph',
            'verified' => false,
            'note' => 'TODO: verify the address and the catalog search pattern.',
        ],

        [
            'key' => 'library-of-congress',
            'name' => 'Library of Congress',
            'url_template' => 'https://www.loc.gov/search/?q={query}',
            'verified' => true,
            'note' => 'Searches the whole loc.gov site with the search text.',
        ],

        [
            'key' => 'librarylink',
            'name' => 'LibraryLink Catalog',
            'url_template' => 'https://librarylink.com',
            'verified' => false,
            'note' => 'TODO: ask the teacher/librarian for the exact LibraryLink catalog address.',
        ],

        [
            'key' => 'miriam-college',
            'name' => 'Miriam College Library',
            'url_template' => 'https://www.miriam.edu.ph',
            'verified' => false,
            'note' => 'TODO: verify the library catalog address.',
        ],

    ],

];
