# MG Planner - Gestion de Planning Collaborateurs

## Présentation

MG Planner est une application web de gestion et suivi des dossiers affectés aux collaborateurs d'une entreprise. Elle permet de planifier, valider, suivre les retards et gérer les portefeuilles de dossiers attribués à chaque utilisateur.

Cette application vise à faciliter la coordination entre chefs de projet et collaborateurs, optimiser le respect des deadlines, et assurer un suivi clair et dynamique des plannings.

---

## Fonctionnalités principales

- **Gestion des plannings** : Visualisation globale et personnelle des dossiers avec dates de début et fin.
- **Validation des dossiers** : Possibilité de valider ou annuler la validation des dossiers.
- **Gestion des retards** : Marquer un dossier en retard ou relancer son traitement.
- **Commentaires dynamiques** : Ajouter/modifier des commentaires associés aux dossiers.
- **Gestion des portefeuilles** : Classement des dossiers par portefeuilles attribués à des chefs.
- **Calendrier interactif** : Vue calendrier avec affichage des dossiers planifiés.
- **Filtres de recherche** : Recherche rapide dans les tableaux et listes.
- **Modification des dates de fin** : Interface modale pour ajuster les dates limites des dossiers.

---

## Installation

1. Clonez ce dépôt :  
   ```bash
   git clone https://github.com/votre-utilisateur/mg-planner.git
   cd mg-planner
2. Configurer la base de données :

  Importer le fichier SQL de structure et données initiales (à fournir).
  
  Modifier le fichier db_connect.php avec tes paramètres de connexion.

3. Déployer les fichiers sur un serveur supportant PHP (Apache, Nginx avec PHP-FPM, etc.).

4. Accéder à l’application via un navigateur.

Structure de la base de données (extrait)
utilisateurs : gestion des utilisateurs, noms, rôles, etc.

dossiers : informations sur les dossiers, dates, statut, code dossier, etc.

affectation : liaison entre utilisateurs et dossiers (planning).

---

## Utilisation
Se connecter avec un utilisateur valide.

Naviguer entre les onglets : Planning global, Mon planning, Mes dossiers, Calendrier.

Cliquer sur les boutons pour valider un dossier, marquer un retard ou modifier les commentaires.

Utiliser la recherche pour filtrer les informations affichées.

Contribution
Les contributions sont bienvenues !
Merci de faire une branche, puis une Pull Request claire et documentée.

Prochaines évolutions prévues
Gestion avancée des portefeuilles et assignations.

Optimisation de la gestion des retards et notifications.

Interface utilisateur améliorée.

Mise en place d’une API REST sécurisée.

---

## Contact
Pour toute question ou demande, merci de me contacter N'jie Zamon via GitHub ou sur mon site njie-zamon.com.

---

## Licence
Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.
