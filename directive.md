MEH — Référentiel fonctionnel et technique du projet
Version de travail : 13 novembre 2026
Statut : document de référence évolutif — décisions confirmées et points à définir distingués
1. Rôle de ce document
Ce document décrit le fonctionnement attendu de l'application MEH : ses utilisateurs, ses droits, ses fonctionnalités, ses règles métier, ses comportements et les décisions prises pendant la conception.

Il est destiné à être transmis à une autre IA ou à un développeur afin qu'il puisse comprendre le projet et poursuivre le travail sans réinventer les décisions déjà prises.
Convention de lecture
- [VALIDÉ] : décision explicitement confirmée par le porteur du projet.
- [PROVISOIRE] : orientation retenue pour avancer, mais qui peut encore être revue.
- [À DÉFINIR] : sujet non tranché. Ne pas inventer une règle.
- [NON PRIORITAIRE] : fonctionnalité souhaitée, mais à réaliser après les fonctions essentielles.
- [PROPOSITION] : idée technique ou fonctionnelle proposée, pas encore validée.
En cas de contradiction entre une ancienne idée et une décision plus récente explicitement confirmée, appliquer la décision la plus récente.
2. Présentation générale et périmètre
MEH est une application de gestion et de consultation de plannings et d'activités pour le ministère concerné par le projet.

L'application doit permettre aux directions de préparer et gérer leurs activités, de publier leurs plannings, de partager certaines activités avec des personnes autorisées et de conserver un historique des opérations.

Le modèle de planning doit rester général et polyvalent. À ce stade, aucun modèle métier spécialisé d'activité n'a été confirmé. Il ne faut donc pas supposer que l'application est uniquement destinée aux réunions, missions, déplacements ou à un autre type précis d'événement.
Contraintes générales
- [VALIDÉ] Application accessible en ligne via un serveur central.
- [VALIDÉ] Environ 100 utilisateurs sont envisagés.
- [VALIDÉ] L'application doit fonctionner sur ordinateur, tablette et téléphone.
- [VALIDÉ] Interface adaptative.
- [VALIDÉ POUR LA V1] Fonctionnement en ligne uniquement. Aucun mode hors ligne ni mécanisme de cache/synchronisation hors ligne n'est prévu dans la première version.
- [NON PRIORITAIRE] Une PWA installable sur mobile pourra être étudiée ultérieurement ; elle ne doit pas retarder la V1.
- [VALIDÉ] Interface : Blade + Livewire + Alpine.js.
- [VALIDÉ] Style : Tailwind CSS.
- [VALIDÉ] Calendrier : FullCalendar Standard uniquement, avec les fonctionnalités gratuites sous licence MIT ; ne pas introduire d'extension Premium payante.
3. Organisation administrative représentée
L'organisation actuellement communiquée comprend le Secrétariat général (SG), quatre groupes et quinze directions.

Répartition connue :
- Énergie : 5 directions.
- Hydrocarbures : 3 directions.
- Ressources : 4 directions.
- Autres : 3 directions.
- Total : 15 directions.
Chaque direction peut avoir un ou plusieurs chefs de service dans l'organisation réelle. Cependant, le rôle applicatif « Chef de service » est exclu du périmètre initial V1 (voir section 4). Cette réalité organisationnelle ne signifie donc pas que des comptes ou permissions Chef de service doivent être développés dès la première version.

Les noms définitifs des quinze directions doivent être fournis/confirmés ultérieurement.
Structure conceptuelle
- Le ministère contient des groupes.
- Un groupe contient plusieurs directions.
- Une direction appartient à un seul groupe.
- Une direction est rattachée à un directeur en fonction.
- Le SG dispose d'une visibilité transversale sur les directions.
- L'administrateur de l'application est distinct de la hiérarchie métier.
[À DÉFINIR] Historisation des changements de rattachement d'une direction à un groupe, dates d'effet et règles de transfert.
4. Utilisateurs, rôles et permissions
4.1 Rôles de la V1
La V1 doit se limiter aux rôles applicatifs suivants :
1. Administrateur.
2. Secrétaire général (SG).
3. Directeur.
Important : le rôle Chef de service ne fait pas partie du modèle initial V1. Il avait été envisagé dans les premières discussions, mais il a ensuite été explicitement écarté du périmètre initial. Il pourra éventuellement être étudié dans une version ultérieure. Ne pas ajouter de table, rôle ou permission Chef de service à la V1 sans nouvelle validation.
4.2 Directeur
- [VALIDÉ] Un directeur gère les activités et le planning de sa propre direction.
- [VALIDÉ] Un directeur ne peut consulter ni gérer les activités/plannings des autres directions.
- [VALIDÉ] Le cloisonnement doit être appliqué côté serveur, pas seulement en masquant des éléments dans l'interface.
- [À DÉFINIR] Détail exhaustif des opérations autorisées au directeur (suppression définitive, restauration, archivage, export, etc.).
4.3 Secrétaire général (SG)
- [VALIDÉ] Le SG peut consulter les plannings de toutes les directions.
- [PROVISOIRE] Des droits de modification du SG ont été évoqués comme une possibilité future, mais ne doivent pas être considérés comme acquis sans décision explicite.
- [VALIDÉ] Le SG fait partie des personnes autorisées à consulter l'historique général/audit.
- [À DÉFINIR] Possibilité pour le SG de créer des activités propres au SG ou de gérer un planning institutionnel transversal.
4.4 Administrateur
- [VALIDÉ] Un rôle administrateur existe.
- [VALIDÉ] L'administrateur gère les comptes et les opérations d'administration.
- [VALIDÉ] L'administrateur valide les demandes de création de compte.
- [VALIDÉ] L'administrateur peut consulter les utilisateurs connectés et les sessions de leurs comptes.
- [VALIDÉ] L'administrateur peut déconnecter un compte à distance ; cette fonction est non prioritaire et prévue en fin de projet.
- [VALIDÉ] L'administrateur reçoit une notification lorsqu'un utilisateur signale une connexion comme suspecte.
- [VALIDÉ] Lors d'une déconnexion à distance, l'administrateur reçoit une notification liée à cette action.
- [À DÉFINIR] Liste exhaustive des permissions administrateur, gestion des rôles, des groupes et des directions, et séparation éventuelle entre plusieurs administrateurs.
- [CONTEXTE ACTUEL] Le porteur du projet est l'administrateur initial envisagé, mais les modalités de création de ce compte initial restent à formaliser.
4.5 Règle de cloisonnement
Le contrôle d'accès doit être vérifié côté serveur pour chaque requête pertinente : consultation, recherche, modification, export, historique, notifications, partage et données hors ligne.

Un directeur ne doit jamais obtenir des données d'une autre direction en modifiant un identifiant dans une URL, un paramètre, une requête API ou une recherche.

Le SG peut consulter les données des quinze directions.
5. Comptes et inscription
- [VALIDÉ] Les comptes sont créés spécifiquement pour l'application MEH ; il n'existe pas nécessairement de compte ministériel préexistant pour chaque agent.
- [VALIDÉ] Le processus de création de compte combine une demande/inscription de l'utilisateur et une validation par l'administrateur.
- [À DÉFINIR] Étapes précises de la demande : champs obligatoires, choix ou attribution du rôle, rattachement à une direction, pièces justificatives éventuelles, refus et motifs.
- [À DÉFINIR] Méthode de vérification de l'adresse e-mail avant activation.
- [À DÉFINIR] Procédure d'activation du premier compte administrateur.
6. Authentification et sécurité des comptes
6.1 Connexion
- [VALIDÉ] L'adresse e-mail est l'identifiant de connexion.
- [VALIDÉ] Un mot de passe est obligatoire.
- [VALIDÉ] La double authentification (2FA) n'est pas prévue dans la V1.
- [VALIDÉ] Après cinq tentatives de connexion échouées, le compte est verrouillé.
- [À DÉFINIR] Durée du verrouillage, déverrouillage automatique ou manuel, et information transmise à l'utilisateur et à l'administrateur.
- [VALIDÉ POUR LE MOMENT] Aucune règle particulière de complexité du mot de passe n'a été imposée. Cela ne dispense pas de stocker les mots de passe avec un mécanisme de hachage robuste et de sécuriser leur traitement.
6.2 Mot de passe oublié
- [VALIDÉ] La récupération passe par un lien envoyé par e-mail.
- [VALIDÉ] Le lien est sécurisé, temporaire et utilisable une seule fois.
- [À DÉFINIR] Durée exacte de validité du lien et contenu du message.
6.3 Changement de mot de passe
- [VALIDÉ] Lorsqu'un utilisateur change son mot de passe, toutes les sessions actives de son compte sont invalidées, y compris la session utilisée pour effectuer le changement.
- L'utilisateur doit ensuite se reconnecter avec le nouveau mot de passe.
7. Sessions et appareils
7.1 Sessions simultanées
- [VALIDÉ] Un même compte peut être connecté simultanément sur plusieurs appareils.
- [VALIDÉ] Aucune limite de nombre de sessions n'est fixée pour le moment.
7.2 Durée et renouvellement
- [VALIDÉ] La durée de validité d'une session est de sept jours.
- [VALIDÉ] La validité est glissante : chaque utilisation de l'application renouvelle automatiquement la session pour sept jours à compter de l'activité prise en compte.
- Si l'utilisateur reste inactif pendant sept jours, la session expire.
- [À DÉFINIR TECHNIQUEMENT] Définir précisément quelles requêtes/activités renouvellent la session, le mécanisme de révocation et le traitement des sessions expirées.
7.3 Déconnexion manuelle
- [VALIDÉ] Le bouton « Déconnexion » ferme uniquement la session de l'appareil actuellement utilisé.
- Les autres sessions du compte sur les autres appareils restent actives.
7.4 Informations visibles par l'administrateur
L'administrateur doit pouvoir consulter, pour chaque compte :
- Le nombre de sessions actives.
- Les appareils sur lesquels le compte est connecté.
- Pour chaque session :
  - type d'appareil (ordinateur, téléphone, tablette) ;
  - système d'exploitation (Windows, Android, Linux, etc.) ;
  - date et heure de connexion ;
  - date et heure de dernière activité.
- [VALIDÉ] Le navigateur utilisé ne doit pas être affiché dans cette vue.
7.5 Déconnexion à distance
- [VALIDÉ] L'administrateur pourra déconnecter un compte à distance.
- [NON PRIORITAIRE] La fonctionnalité est à réaliser en fin de projet, après les fonctionnalités essentielles.
- [VALIDÉ] L'administrateur reçoit une notification lors de cette action.
- [À DÉFINIR] Déconnexion d'une session précise ou de toutes les sessions d'un compte ; les discussions précédentes établissent la déconnexion d'un compte, mais le détail de l'interface reste à préciser.
7.6 Notification de nouvelle connexion
- [VALIDÉ] À chaque nouvelle connexion, l'utilisateur reçoit une notification interne, même s'il s'agit d'un appareil déjà utilisé.
- La notification indique au minimum le type d'appareil, le système d'exploitation et la date/heure de connexion.
7.7 Connexion suspecte
- [VALIDÉ] La notification de nouvelle connexion propose une action « Ce n'était pas moi ».
- Cette action envoie un signalement/une alerte à l'administrateur.
- [VALIDÉ] Le signalement ne bloque pas automatiquement le compte et ne déconnecte pas automatiquement les sessions.
- [VALIDÉ] Le compte reste actif ; seul l'administrateur décide des mesures à prendre.
- [À DÉFINIR] Processus de traitement par l'administrateur, statut du signalement, résolution et information de l'utilisateur.
8. Activités et plannings
8.1 Principe général
- [VALIDÉ] L'application gère des activités dans un planning général et polyvalent.
- [VALIDÉ] Aucune limite mensuelle ou annuelle n'est imposée au planning. Les utilisateurs peuvent organiser leurs activités sur toute période nécessaire.
- [VALIDÉ] Une activité peut être ponctuelle ou s'étendre sur plusieurs jours.
- [À DÉFINIR] Champs définitifs d'une activité, formats de dates/heures, fuseau horaire, activités sans horaire précis, récurrence et gestion des journées entières.
- [PROPOSITION À VALIDER] Champs possibles : titre, description, date/heure de début et de fin, lieu, responsable, participants, priorité, statut et observations. Cette liste est une proposition de conception, pas une validation définitive de tous les champs.
8.2 Brouillon et publication
- [VALIDÉ] Une activité nouvellement créée est enregistrée en brouillon.
- [VALIDÉ] Une activité en brouillon est invisible aux autres utilisateurs.
- [VALIDÉ] L'auteur/la direction peut travailler sur son brouillon avant publication.
- [VALIDÉ] Les autres utilisateurs ne voient l'activité qu'après sa publication, selon leurs permissions.
- [À DÉFINIR] Possibilité de dépublier une activité déjà publiée, et conséquences sur les personnes avec lesquelles elle est partagée.
- [À DÉFINIR] Workflow de validation éventuel. À ce stade, le SG est considéré comme n'ayant pas besoin d'une étape de demande de validation pour que les activités soient considérées valides.
8.3 Chevauchement des activités
- [VALIDÉ] Les chevauchements sont autorisés.
- Lorsqu'une activité chevauche une autre activité pertinente, l'application affiche un avertissement.
- L'utilisateur peut décider de continuer malgré l'avertissement.
- L'application ne doit donc pas bloquer automatiquement la création ou la modification à cause d'un chevauchement.
- [À DÉFINIR] Portée exacte du contrôle : activités de la même direction, du même responsable, ou autre règle. Ne pas supposer qu'un chevauchement entre deux directions est nécessairement signalé.
8.4 Consultation des plannings
- Les directeurs consultent et gèrent le planning de leur propre direction.
- Le SG consulte les plannings de toutes les directions.
- [À DÉFINIR] Vues calendrier (jour/semaine/mois), liste, filtres, recherche, export et affichage consolidé du SG.
8.5 Modifications et versions
- [VALIDÉ DANS LE BESOIN] L'historique des activités et les versions doivent être conservés.
- Les modifications importantes doivent pouvoir être retracées.
- [À DÉFINIR] Stratégie exacte de versionnement : quelles modifications créent une version, contenu complet ou différences, possibilité de comparer/restaurer une version et visibilité des versions.
8.6 Report ou changement de date
- [À DÉFINIR] Règles de report, annulation, changement d'horaire, conservation de l'ancienne date et notifications aux personnes concernées. Ne pas inventer de workflow avant validation.
9. Partage des activités et invitations
- [VALIDÉ] Le partage est sélectif : un utilisateur peut partager certaines activités, et non obligatoirement tout son planning.
- [VALIDÉ] Seules les activités publiées peuvent être partagées.
- [VALIDÉ] Les brouillons ne peuvent jamais être partagés.
- [VALIDÉ] Le partage donne un accès en lecture seule.
- [VALIDÉ] L'accès partagé peut être révoqué par la personne autorisée à gérer le partage.
- [À DÉFINIR] Destinataires possibles : utilisateurs MEH uniquement, personnes externes, ou les deux.
- [À DÉFINIR] Mécanisme d'invitation : invitation liée à un compte, lien privé, code d'accès ou autre.
- [À DÉFINIR] Durée de validité, expiration, nombre d'utilisations, transfert du lien, et protection contre le partage non souhaité.
- [À DÉFINIR] Informations exactes visibles par un invité externe et éventuelle anonymisation des participants.
- [À DÉFINIR] Règles de notification lors d'une invitation, acceptation, révocation ou modification de l'activité partagée.
Important : le partage ne doit jamais permettre de contourner le cloisonnement des directions ni de donner un droit de modification à un destinataire en lecture seule.
10. Notifications internes
- [VALIDÉ] Les notifications de la V1 sont internes à l'application.
- Les notifications par e-mail ou SMS ne sont pas retenues comme canal général de notification V1, sauf e-mails techniques nécessaires à l'authentification (par exemple, récupération de mot de passe).
- [VALIDÉ] Une notification est créée à chaque nouvelle connexion.
- [VALIDÉ] L'utilisateur peut signaler une nouvelle connexion avec « Ce n'était pas moi », ce qui alerte l'administrateur.
- [VALIDÉ] L'administrateur est notifié lorsqu'il effectue une déconnexion à distance.
- [À DÉFINIR] Catalogue complet des événements qui génèrent une notification : invitations, partages, révocations, changements d'activité, archivage, suppression, etc.
- [À DÉFINIR] États des notifications (non lue/lue), suppression, conservation, préférences et règles de rétention.
Les actions de lecture et de suppression des notifications doivent être journalisées dans l'audit.
11. Suppression logique et corbeille
- [VALIDÉ] La suppression d'une activité est logique : l'enregistrement n'est pas effacé immédiatement de la base de données.
- [VALIDÉ] Une activité supprimée disparaît des vues courantes/plannings ordinaires et est placée dans la corbeille.
- [À DÉFINIR] Qui peut restaurer une activité, qui peut la supprimer définitivement, durée de conservation dans la corbeille et comportement des activités partagées après suppression.
- [À DÉFINIR] Journalisation et notification lors d'une suppression, restauration ou suppression définitive.
Ne pas confondre la corbeille avec les archives : ce sont deux espaces et deux usages distincts.
12. Archivage
- [VALIDÉ] Un espace « Archives » distinct de la corbeille est prévu.
- L'archivage sert à conserver les activités terminées pour consultation ultérieure, sans encombrer les vues courantes.
- [À DÉFINIR] Archivage manuel ou automatique.
- [À DÉFINIR] Conditions d'archivage (activité terminée, date dépassée, action de l'utilisateur, etc.).
- [À DÉFINIR] Qui peut archiver, consulter, restaurer ou désarchiver.
- [À DÉFINIR] Possibilité de modifier une activité archivée et règles de versionnement.
- [À DÉFINIR] Durée de conservation des archives.
13. Documents et pièces jointes
- [À DÉFINIR] L'ajout de documents aux activités a été évoqué, mais leur finalité n'a pas encore été décidée.
- Ne pas développer un système de documents complet avant d'avoir défini les usages : pièces préparatoires, comptes rendus, ordres de mission, justificatifs, documents de référence, etc.
- [À DÉFINIR] Formats autorisés, taille maximale, stockage, droits de téléchargement, partage, antivirus, rétention et suppression.
14. Administration de l'organisation
- [VALIDÉ] L'application doit disposer d'une administration.
- [À DÉFINIR] Écrans et opérations administratives exacts.
- La structure organisationnelle comprend les groupes et les directions.
- [VALIDÉ DANS L'AUDIT] Les modifications des groupes et directions doivent être tracées.
- [À DÉFINIR] Création, renommage, désactivation, déplacement et suppression logique d'un groupe ou d'une direction.
- [À DÉFINIR] Conséquences d'une désactivation sur les utilisateurs, activités, partages et historiques.
- Toute modification de structure doit préserver l'intégrité des anciennes données et l'historique.
15. Départ et remplacement d'un responsable
- [VALIDÉ] Lorsqu'un directeur quitte sa fonction, son compte est désactivé.
- [VALIDÉ] Son historique et les opérations qu'il a réalisées doivent être conservés ; il ne faut pas supprimer son compte ni effacer ses traces.
- Le nouveau responsable doit pouvoir disposer de son propre compte et être rattaché à la direction selon la procédure administrative.
- [À DÉFINIR] Procédure exacte de nomination du nouveau directeur, transfert éventuel des responsabilités sur les brouillons et activités, traitement des sessions de l'ancien directeur et date d'effet.
- Ne jamais attribuer rétroactivement les actions de l'ancien directeur au nouveau : l'auteur réel d'une action doit rester identifiable dans l'audit.
16. Historique, versions et audit
16.1 Principe
- [VALIDÉ] L'historique est essentiel au projet.
- [VALIDÉ] Les actions doivent être enregistrées avec au minimum : qui a fait quoi, quand et pourquoi (lorsqu'un motif est applicable).
- [VALIDÉ] Les traces d'audit sont immuables : un utilisateur ne doit pas pouvoir modifier ou effacer les traces historiques.
- [VALIDÉ] L'historique général est consultable uniquement par l'administrateur et le SG.
- [VALIDÉ] Le cloisonnement des directions s'applique également aux données d'historique accessibles aux autres rôles.
16.2 Événements à journaliser
L'étape 6 du modèle de données a été validée pour journaliser notamment :
- connexions et déconnexions ;
- gestion des comptes ;
- invitations et partages ;
- consultations des activités ;
- lecture et suppression des notifications ;
- actions de l'administrateur ;
- modifications des groupes et des directions.
Les opérations métier sur les activités et leurs versions doivent également être traçables selon le modèle de versionnement à préciser.
16.3 Données possibles d'une trace
[PROPOSITION TECHNIQUE À VALIDER] Une trace pourrait comporter : identifiant de l'acteur, rôle au moment de l'action, action, type et identifiant de la ressource, date/heure, motif/commentaire lorsqu'applicable, résultat et métadonnées techniques utiles.

Ne pas enregistrer de mots de passe, jetons de session ou secrets dans les journaux.
16.4 Accès à l'historique
- Administrateur : accès à l'historique général.
- SG : accès à l'historique général.
- Directeur : aucun accès global ; les éventuelles traces limitées à sa direction restent à définir.
- [À DÉFINIR] Filtres, recherche, export, durée de conservation et pagination.
17. Sécurité technique et confidentialité
Principes à respecter pendant l'implémentation :
- Autorisation côté serveur pour chaque ressource et chaque action.
- Cloisonnement strict des données entre directions.
- Mots de passe hachés avec un mécanisme robuste ; jamais stockés en clair.
- Jetons et identifiants de session protégés, révocables et non exposés.
- Liens de récupération temporaires et à usage unique.
- Liens de partage protégés et révocables.
- Journal d'audit non modifiable par les utilisateurs.
- Validation des entrées et protection contre les injections SQL, XSS, CSRF et autres attaques web pertinentes.
- Contrôle des fichiers téléversés si les pièces jointes sont retenues.
- Ne pas exposer les données d'une autre direction par les recherches, filtres, exports, notifications, caches ou données hors ligne.
- Les données hors ligne doivent respecter les mêmes droits et être protégées sur l'appareil ; la stratégie de synchronisation reste à définir.
18. Environnement technique retenu
- [VALIDÉ] Backend : Laravel.
- [VALIDÉ] Langage côté serveur : PHP.
- [VALIDÉ] Base de données : MySQL/MariaDB.
- [ENVIRONNEMENT CONFIRMÉ] MariaDB 11.4.13 est installée séparément de XAMPP sur l'environnement de développement Windows ; le service Windows porte le nom MariaDB.
- [VALIDÉ] Interface : Blade + Livewire + Alpine.js.
- [VALIDÉ] CSS : Tailwind CSS.
- [VALIDÉ] Calendrier : FullCalendar Standard uniquement (fonctionnalités gratuites sous licence MIT ; aucune extension Premium payante).
- [VALIDÉ] Interface adaptative pour ordinateur, tablette et téléphone.
- [VALIDÉ POUR LA V1] Application en ligne uniquement.
- [NON PRIORITAIRE] PWA reportée après la V1 ; mode hors ligne et synchronisation hors ligne non inclus dans la V1.
- [À CONFIRMER PAR INSPECTION DU PROJET] Versions exactes de Laravel et PHP utilisées par le dépôt.
- [À DÉFINIR] Hébergement, nom de domaine, certificat TLS, sauvegardes, supervision et environnements de développement/test/production.
La base de données et le modèle relationnel ont été conçus. Les modèles Eloquent et les migrations Laravel sont désormais considérés comme terminés et validés (état confirmé le 12 novembre 2026). Ne pas reprendre leur conception ni proposer de les régénérer sans problème technique concret démontré.
19. Proposition de découpage fonctionnel du développement
Ordre indicatif, à adapter après validation du modèle de données :
1. Organisation : groupes et directions.
2. Comptes, rôles, inscription et validation administrative.
3. Authentification, récupération du mot de passe et sessions.
4. Permissions et cloisonnement des directions.
5. Activités : création, brouillon, modification et publication.
6. Vues de planning et contrôle des chevauchements.
7. Partages et invitations.
8. Notifications internes.
9. Corbeille et restauration.
10. Archives.
11. Historique des activités et audit général.
12. Administration avancée, sessions visibles et déconnexion à distance.
13. PWA et fonctionnement hors ligne en lecture, selon le périmètre défini.
14. Tests de sécurité, tests fonctionnels et déploiement.
Ce découpage est une proposition d'organisation du travail, pas une validation de toutes les règles fonctionnelles encore ouvertes.
20. Décisions confirmées à ne pas remettre en question sans demande
Récapitulatif des décisions explicites :
1. Backend Laravel et PHP ; base MySQL/MariaDB.
2. Application en ligne, adaptative sur ordinateur/tablette/mobile ; PWA envisagée.
3. Structure connue : 4 groupes (Énergie 5 directions, Hydrocarbures 3, Ressources 4, Autres 3), soit 15 directions.
4. Rôles V1 : Administrateur, SG, Directeur. Chef de service exclu de la V1.
5. Directeur limité à sa propre direction ; SG peut consulter toutes les directions.
6. Les activités sont créées en brouillon et invisibles aux autres avant publication.
7. Le planning n'a pas de limite de durée.
8. Les chevauchements sont autorisés avec avertissement, sans blocage automatique.
9. Les partages sont sélectifs, limités aux activités publiées, en lecture seule et révocables.
10. Les notifications V1 sont internes.
11. L'historique/audit est essentiel et les traces sont immuables.
12. L'historique général est accessible uniquement à l'administrateur et au SG.
13. Suppression logique des activités avec corbeille.
14. Archives distinctes de la corbeille pour conserver les activités terminées.
15. Comptes créés pour l'application ; demande/inscription suivie d'une validation administrative.
16. Connexion par adresse e-mail et mot de passe.
17. Pas de 2FA en V1.
18. Verrouillage après cinq tentatives échouées ; durée et procédure à définir.
19. Réinitialisation par lien e-mail sécurisé, temporaire et à usage unique.
20. Sessions simultanées autorisées sans limite définie.
21. Une session a une validité glissante de sept jours, renouvelée par l'utilisation ; expiration après sept jours d'inactivité.
22. Déconnexion manuelle : ferme uniquement la session de l'appareil courant.
23. Changement de mot de passe : invalide toutes les sessions.
24. L'utilisateur reçoit une notification interne à chaque nouvelle connexion, avec type d'appareil, système d'exploitation et date/heure.
25. L'utilisateur peut signaler « Ce n'était pas moi » ; l'administrateur est alerté, mais le compte n'est pas bloqué automatiquement.
26. L'administrateur voit le nombre de sessions et les appareils ; détails : type, OS, connexion, dernière activité. Le navigateur n'est pas affiché.
27. La déconnexion à distance par l'administrateur est prévue, mais non prioritaire et à développer en fin de projet.
28. Le départ d'un directeur entraîne la désactivation de son compte, avec conservation de son historique et de ses opérations.
21. Questions encore ouvertes — ne pas inventer les réponses
Les sujets suivants nécessitent une décision ou une spécification complémentaire :
Organisation et comptes
- Noms officiels des quinze directions.
- Données exactes d'un compte et formulaire d'inscription.
- Vérification de l'e-mail.
- Procédure de création du premier administrateur.
- Gestion de plusieurs SG et de leurs comptes.
- Gestion détaillée des rôles et permissions administrateur.
Activités
- Liste finale des champs d'une activité.
- Gestion des participants et responsables.
- Activités sur plusieurs jours, journée entière et récurrence.
- Règle précise de détection des chevauchements.
- Dépublication, annulation et report.
- Règles de versionnement et restauration d'une ancienne version.
- Vues, filtres, recherche et export.
- Possibilité de joindre des documents et finalité de ces documents.
Partage
- Destinataires internes et/ou externes.
- Mécanisme d'invitation et d'accès externe.
- Expiration des invitations et liens.
- Informations visibles par un invité.
- Notifications liées aux partages.
Corbeille et archives
- Qui peut restaurer ou supprimer définitivement.
- Durée de conservation de la corbeille.
- Archivage manuel ou automatique.
- Conditions et droits de restauration/désarchivage.
- Durée de conservation des archives.
Notifications et audit
- Catalogue complet des notifications.
- Durée de conservation des notifications et traces d'audit.
- Filtres et exports de l'historique.
- Processus de traitement des connexions suspectes.
- Motifs obligatoires pour certaines actions sensibles.
Sessions et sécurité
- Durée du verrouillage après cinq échecs.
- Définition exacte de l'activité qui renouvelle la session.
- Détail de la déconnexion à distance : une session ou toutes les sessions.
- Mesures administratives après un signalement suspect.
- Règles de fonctionnement hors ligne et de synchronisation.
Technique et exploitation
- Interface utilisateur.
- Versions de PHP, Laravel et MySQL/MariaDB.
- Hébergement et déploiement.
- Sauvegardes et restauration.
- Tests et supervision.
22. Consignes à une IA ou à un développeur qui reprend le projet
1. Lire ce document avant de proposer une architecture ou d'écrire du code.
2. Respecter les décisions marquées [VALIDÉ].
3. Ne pas réintroduire le rôle Chef de service dans la V1.
4. Ne pas transformer les avertissements de chevauchement en blocage.
5. Ne pas rendre visibles les brouillons aux autres utilisateurs.
6. Ne pas permettre le partage d'un brouillon.
7. Ne jamais donner un accès transversal à un directeur.
8. Appliquer les permissions côté serveur, pas uniquement dans l'interface.
9. Ne pas rendre modifiables les partages en lecture seule.
10. Ne pas confondre archives et corbeille.
11. Ne pas supprimer les traces historiques lors du départ d'un responsable.
12. Ne pas inventer de réponses aux points [À DÉFINIR].
13. Lorsqu'une décision nouvelle est prise, mettre à jour ce document et conserver la distinction entre validé, provisoire et à définir.
14. Avant de poser une question, vérifier qu'elle n'a pas déjà été tranchée dans ce document ou dans les décisions plus récentes du projet.
15. Pour l'état actuel du MEH, considérer le modèle de données, les modèles Eloquent et les migrations comme validés ; ne les modifier qu'en présence d'un problème concret.
16. Inspecter le dépôt et sa version réelle avant de créer ou modifier des fichiers ; ne jamais supposer qu'un fichier, une route ou un composant existe.
17. Expliquer les choix techniques et leurs conséquences au porteur du projet ; avancer progressivement et faire valider les décisions structurantes.
18. À la fin de chaque étape, fournir un compte rendu des fichiers créés/modifiés, des décisions prises, des commandes/tests exécutés et de leurs résultats, ainsi que de la prochaine étape. Ne déclarer une étape terminée qu'après vérification.
22 bis. Protocole de travail pour les développeurs et agents IA
Cette section complète les consignes de la section 22. Elle décrit la manière de travailler sur le dépôt MEH.
Avant toute intervention
1. Lire entièrement directive.md et vérifier les décisions les plus récentes du projet.
2. Examiner les fichiers concernés dans le dépôt réel ; ne pas se fier uniquement à un ancien rapport ou à une description de conversation.
3. Vérifier la version de Laravel et la structure du projet avant de proposer une commande ou un emplacement de fichier.
4. Vérifier si une fonctionnalité existe déjà avant d'ajouter un package, un contrôleur, un middleware, une route, une migration ou un service.
5. Distinguer clairement les faits observés, les décisions validées, les propositions et les questions ouvertes.
Pendant l'intervention
1. Travailler sur une tâche limitée et annoncée.
2. Ne pas modifier des fichiers sans rapport avec la tâche.
3. Ne jamais modifier ou supprimer une migration, un modèle ou une décision validée sans expliquer le problème concret et obtenir l'accord du porteur du projet.
4. Ne pas ajouter de dépendance Composer ou NPM sans expliquer son utilité, son coût, sa licence et les alternatives.
5. Ne pas introduire de fonctionnalité Premium payante de FullCalendar.
6. Respecter les règles d'autorisation côté serveur et ne jamais se limiter au masquage de l'interface.
7. Ne jamais exposer de mot de passe, secret, clé API, cookie, jeton ou identifiant de session dans un rapport, un commit ou un journal.
8. Ne pas inventer les résultats de commandes ou de tests. Indiquer explicitement lorsqu'un test n'a pas été exécuté.
À la fin de chaque tâche
Fournir obligatoirement :
- Objectif de la tâche.
- Fichiers inspectés.
- Fichiers créés, modifiés ou supprimés.
- Résumé des changements et raisons techniques.
- Commandes exécutées.
- Tests exécutés et résultats exacts.
- Problèmes connus ou points non vérifiés.
- Décisions qui nécessitent l'accord du porteur du projet.
- Prochaine étape recommandée.
Le statut doit être l'un des suivants : À FAIRE, EN COURS, À TESTER, À CORRIGER ou VALIDÉ.

Une tâche ne passe à VALIDÉ qu'après examen du code et résultats de tests suffisants. La rédaction du code seule ne constitue pas une validation.
Règle de synchronisation documentaire
Lorsqu'une décision est confirmée ou qu'une étape est validée, mettre à jour directive.md dans la même intervention ou signaler précisément les lignes/sections à actualiser. Conserver l'historique des décisions importantes et ne pas transformer une proposition en décision validée sans accord explicite.
23. État actuel du projet — 13 novembre 2026
23.1 Éléments terminés et validés
- La phase de clarification fonctionnelle a produit le présent référentiel.
- L'environnement Laravel/PHP/MariaDB est choisi.
- Le rôle Chef de service est écarté de la V1.
- Le modèle relationnel de référence a été élaboré et les migrations Laravel ainsi que les modèles Eloquent sont terminés et validés.
- Les éléments de base de données ne doivent pas être réexaminés systématiquement ; les rouvrir uniquement si une incohérence ou un défaut concret apparaît pendant le développement.
- Le modèle UserSession existe et a été examiné. Il masque session_key_hash, convertit les dates en objets datetime et possède une relation user().
- config/session.php a été examiné. Le driver effectif confirmé par le porteur du projet est file.
23.2 Authentification et sessions — état de développement
- [À IMPLÉMENTER] Authentification par adresse e-mail et mot de passe, selon les règles de la section 6.
- [EXISTANT, NON VALIDÉ] app/Services/UserSessionService.php contient les méthodes create, updateActivity, revoke, revokeAll, detectDeviceType et detectOperatingSystem. Le service doit être revu et testé avant d'être considéré comme terminé.
- [EXISTANT, VIDE AU DERNIER CONTRÔLE] app/Http/Middleware/TrackUserSession.php est présent, mais son implémentation reste à réaliser.
- [NON CONFIRMÉ / À INSPECTER] AuthenticatedSessionController et les vues d'authentification ne sont pas encore confirmés comme existants.
- [ÉTAT OBSERVÉ] routes/web.php ne contenait que la route GET / vers la vue welcome lors du dernier contrôle.
- [ÉTAT OBSERVÉ] app/Http/Controllers/Controller.php ne contenait que la classe abstraite de base.
- [VALIDÉ POUR LE DÉVELOPPEMENT] Plusieurs sessions simultanées sont autorisées pour un même utilisateur.
- [VALIDÉ POUR LE DÉVELOPPEMENT] Chaque utilisateur ne peut consulter et révoquer que ses propres sessions. La déconnexion normale ne révoque que la session courante.
- [VALIDÉ POUR LE DÉVELOPPEMENT] La table user_sessions conserve uniquement le hash SHA-256 de l'identifiant de session, jamais l'identifiant original.
- [VALIDÉ POUR LE DÉVELOPPEMENT] user_sessions sert de registre de contrôle : le middleware doit vérifier qu'une session authentifiée possède un enregistrement actif correspondant. Une session révoquée doit être refusée lors de sa prochaine requête ; une session distante totalement inactive n'est donc pas interrompue instantanément.
- [À VÉRIFIER TECHNIQUEMENT] Le driver file stocke le contenu des sessions séparément de la table user_sessions. La révocation doit donc être appliquée par le middleware à chaque requête, sans supposer que le hash permet de retrouver directement le fichier de session.
- [ÉCART À RÉSOUDRE] La règle fonctionnelle validée prévoit une durée glissante de sept jours (section 7), tandis que la configuration config/session.php observée utilise une durée par défaut de 120 minutes. Ne pas considérer ces valeurs comme équivalentes : définir et tester une configuration cohérente avec la durée métier de sept jours.
- [À IMPLÉMENTER] AuthenticatedSessionController, UserSessionService finalisé et TrackUserSession, puis routes, vues, tests de connexion, déconnexion, renouvellement et révocation.
23.3 Prochaine étape
Inspecter les fichiers réels du dépôt avant toute modification : composer.json, bootstrap/app.php (ou app/Http/Kernel.php selon la version Laravel), config/auth.php, app/Models/User.php, les contrôleurs, middlewares, services, vues et routes. Identifier la version exacte de Laravel et les éventuels composants déjà installés afin d'éviter les doublons. Ensuite, implémenter progressivement l'authentification et la gestion des sessions, avec tests à chaque étape.

Ne pas revenir à la conception des migrations ou des modèles déjà validés.