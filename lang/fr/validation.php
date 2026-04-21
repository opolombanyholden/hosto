<?php

declare(strict_types=1);

return [
    'accepted' => 'Le champ :attribute doit etre accepte.',
    'active_url' => 'Le champ :attribute n\'est pas une URL valide.',
    'after' => 'Le champ :attribute doit etre une date posterieure au :date.',
    'alpha' => 'Le champ :attribute ne peut contenir que des lettres.',
    'between' => [
        'numeric' => 'La valeur de :attribute doit etre comprise entre :min et :max.',
        'string' => 'Le texte :attribute doit contenir entre :min et :max caracteres.',
    ],
    'confirmed' => 'Le champ de confirmation :attribute ne correspond pas.',
    'email' => 'Le champ :attribute doit etre une adresse email valide.',
    'exists' => 'Le champ :attribute selectionne est invalide.',
    'max' => [
        'numeric' => 'La valeur de :attribute ne peut etre superieure a :max.',
        'string' => 'Le texte de :attribute ne peut contenir plus de :max caracteres.',
        'file' => 'Le fichier :attribute ne peut etre plus grand que :max kilo-octets.',
    ],
    'min' => [
        'numeric' => 'La valeur de :attribute doit etre superieure ou egale a :min.',
        'string' => 'Le texte :attribute doit contenir au moins :min caracteres.',
        'file' => 'Le fichier :attribute doit etre d\'au moins :min kilo-octets.',
    ],
    'numeric' => 'Le champ :attribute doit contenir un nombre.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit etre une chaine de caracteres.',
    'unique' => 'La valeur du champ :attribute est deja utilisee.',
    'url' => 'Le format de l\'URL de :attribute n\'est pas valide.',
    'size' => [
        'numeric' => 'La valeur de :attribute doit etre :size.',
        'string' => 'Le texte de :attribute doit contenir :size caracteres.',
    ],
    'integer' => 'Le champ :attribute doit etre un entier.',
    'boolean' => 'Le champ :attribute doit etre vrai ou faux.',
    'date' => 'Le champ :attribute n\'est pas une date valide.',
    'image' => 'Le champ :attribute doit etre une image.',
    'mimes' => 'Le champ :attribute doit etre un fichier de type : :values.',
    'in' => 'Le champ :attribute selectionne est invalide.',
    'not_in' => 'Le champ :attribute selectionne est invalide.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Le :attribute donne est apparu dans une fuite de donnees. Veuillez choisir un autre :attribute.',
    ],

    'attributes' => [
        'name' => 'nom',
        'email' => 'adresse email',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'phone' => 'telephone',
        'current_password' => 'mot de passe actuel',
        'role' => 'profession',
        'content' => 'contenu',
        'reason' => 'motif',
        'code' => 'code',
    ],
];
