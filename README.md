# PHP Technical Test

## Technologies utilisées

### Back

- PHP : 8.5.1
- Composer : 2.9.3
- Symfony : 8.0.3
- Doctrine ORM : 3.6
- Fixture : 4.3.1
- HttpClient : 8.0.3
- Symfony Scheduler : 8.0.0

### Front

- Twig
- Tailwind CSS : 4.1.18
- DaisyUI : 5.5.14

### Authentification

- league/oauth2-google : 4.1.0
- knpuniversity/oauth2-client-bundle : 2.20.1

### Base de données

- SQLite : 3.42.0

## Installation

### Prérequis

1. Allez sur https://console.cloud.google.com/ et créez un nouveau projet.
2. Si besoin, configurez l'écran de consentement.
3. Créez un nouveau client OAuth de type Application Web.
    - Dans **Origines JavaScript autorisées** mettez `http://localhost:8000`.
    - Dans **URI de redirection autorisés** mettez `http://localhost:8000/oauth/check/google` et
      `http://127.0.0.1:8000/oauth/check/google`.
4. Et récupérez vos identifiants `client_id` et `client_secret`.

Récupérez également la clé API de la NASA : https://api.nasa.gov/.

### Configuration

1. Créez le fichier `.env.local` et déplacez les variables du fichier `.env` dedans puis mettez vos clés :
    ```
   NASA_API_KEY=YOUR_KEY
   GOOGLE_OAUTH_ID=YOUR_KEY
   GOOGLE_OAUTH_SECRET=YOUR_KEY
   ```

2. Installer les dépendances
   ```
   composer install
   ```

3. Installer DaisyUI
   ```
   npm install
   ```

4. Compiler le CSS de Tailwind
   ```
   php bin/console tailwind:build
   ```

5. Exécuter les migrations
   ```
   php bin/console doctrine:migrations:migrate
   ```

   Si la commande échoue, faites celle-là d'abord
   ```
   php bin/console doctrine:database:create
   ```

6. Remplir la base de données avec l'image du jour
   ```
   php bin/console app:fetch-apod
   ```

7. Lancer l'application
   ```
   symfony serve
   ```

### Problèmes éventuels

**SQLite**

Vu que le projet utilise SQLite, assurez-vous que ces deux lignes soient activées dans votre fichier `php.ini` :

- `extension=pdo_sqlite`
- `extension=sqlite3`

**Google OAuth**

Google peut poser problème au niveau des certificats. Dans ce cas, allez sur https://curl.se/ca/cacert.pem et stockez le
certificat sur votre ordinateur.
Dans `php.ini` activez ces lignes si besoin et renseignez le chemin :

```
curl.cainfo = "votre_chemin\cacert.pem"
openssl.cafile = "votre_chemin\cacert.pem"
```

## Déroulement

> ⚠️ ATTENTION : À ce jour, l'API de la NASA est en panne. J'utilise une API de secours et si cette API échoue
> également,
> je retourne des données brutes pour qu'une image puisse s'afficher.

### 1. Migration

J'ai d'abord migré Symfony de la 5.4 vers la 8 en migrant une version majeure à la fois (j'ai testé de faire une
migration d'un coup mais j'avais des erreurs).

### 2. Gestion de l'API

Ensuite, j'ai commencé par la connexion à l'API avec le but d'afficher l'image du jour dans une page. J'ai créé un
Service en utilisant `HttpClient`, un Controller et une Vue.
Malheureusement, durant mon développement l'API de la NASA était en panne (elle l'est toujours à ce jour).
Cela m'a néanmoins permis de gérer les exceptions dans le service de l'API.

### 3. Base de données et jeu de données

J'ai ensuite créé des données de secours pour pouvoir continuer en attendant que ce soit réparé.
D'abord des données créées à la main puis j'ai créé une base de données `SQLite` en utilisant les `Fixtures` pour
remplir cette base.
Par la suite, j'ai créé la commande qui fetch l'image du jour et la persiste dans la base de données en utilisant le
service développé précédemment.

> J'ai rajouté une API de secours avant de terminer le projet

### 4. Gestion du type d'image

Par la suite, avec ma base de données et les Fixtures, j'ai géré le cas où l'image du jour n'est pas une image.
Pour cela, j'ai une fonction dans `PictureRepository` qui renvoie l'image la plus récente en faisant une requête qui
trie les dates et vérifie que le type est bien `image`.

### 5. Sécurisation et authentification

La dernière étape était l'implémentation d'une sécurisation du site avec la nécessité de se connecter en utilisant
Google.
Après avoir configuré les deux librairies `league/oauth2-google` et `knpuniversity/oauth2-client-bundle`, j'ai fait le
choix de rediriger tout utilisateur non connecté sur la page de connexion.
Après s'être connecté, il sera renvoyé sur la page de l'image du jour où il aura la possibilité de se déconnecter dans
la barre de navigation.
La déconnexion renvoie vers la page de connexion.

> J'ai activé l'option qui garde la connexion de l'utilisateur même s'il quitte la page.

### Bonus

J'ai utilisé Symfony Scheduler pour créer une tâche planifiée qui va lancer la commande de fetch chaque jour à une heure
précise.

J'ai testé avec 15 secondes d'intervalle et cela fonctionne. J'ai configuré la tâche pour que la commande soit lancée
quotidiennement à 8h donc en théorie, il suffirait de lancer une fois cette tâche et chaque jour l'image du jour serait
récupérée sans refaire la commande manuellement.

Pour lancer la tâche planifiée :

```
php bin/console messenger:consume scheduler_default
```

# Sujet

## Instructions

The goal of this PHP test is to take you to space with the
[picture of the day by the Nasa](https://apod.nasa.gov/apod/archivepixFull.html). We want to display a page on our
website that will show us the current picture of the day (and its description). To achieve that, NASA gives us an
API to fetch the data from their server. Unfortunately, This API has a limit on the number of calls we can make. So we
will store the images on our side.

Here is an example of the response by the API :

```json
{
  "date": "2021-02-13",
  "explanation": "Get out your red/blue glasses and float next to asteroid 433 Eros. Orbiting the Sun once every 1.8 years, the near-Earth asteroid is named for the Greek god of love. Still, its shape more closely resembles a lumpy potato than a heart. Eros is a diminutive 40 x 14 x 14 kilometer world of undulating horizons, craters, boulders and valleys. Its unsettling scale and unromantic shape are emphasized in this mosaic of images from the NEAR Shoemaker spacecraft processed to yield a stereo anaglyphic view. Along with dramatic chiaroscuro, NEAR Shoemaker's 3-D imaging provided important measurements of the asteroid's landforms and structures, and clues to the origin of this city-sized chunk of Solar System. The smallest features visible here are about 30 meters across. Beginning on February 14, 2000, historic NEAR Shoemaker spent a year in orbit around Eros, the first spacecraft to orbit an asteroid. Twenty years ago, on February 12 2001, it landed on Eros, the first ever landing on an asteroid's surface. NEAR Shoemaker's final transmission from the surface of Eros was on February 28, 2001.",
  "hdurl": "https://apod.nasa.gov/apod/image/2102/PIA02471_800.jpg",
  "media_type": "image",
  "service_version": "v1",
  "title": "Stereo Eros",
  "url": "https://apod.nasa.gov/apod/image/2102/PIA02471_800.jpg"
}
```

For now, we only need these informations that will be displayed on our website, and thus will be saved on our database :

- title ;
- explanation ;
- date ;
- image.

The application will only be accessible by logged in users. To achieve that, the login process will use Google as a
login provider.

Here are the steps you may want to follow to achieve this challenge :

- **Step 1**: make a CLI command that will be executed each day to fetch the picture of the day ;
- **Step 2**: make a page to display the picture of the day. If there is no picture (say the picture of the day is a
  video) we will display the picture of the previous day ;
- **Step 3**: protect our app, so the picture will only be visible by a logged in user. The user will be able to connect
  with a Google account using Google as login provider ;
- **Step 4**: make a small documentation explaining what you did, the technologies you used etc.

To fetch pictures from the NASA API, you need an API key. It will be sent to you by email.

When you finish this challenge, send a link to your repository by email.

## Stack

The only constraint is to use PHP (use the version you want) and this Symfony project. You will then use any library you
want, any database you want.

And most of all, have fun!