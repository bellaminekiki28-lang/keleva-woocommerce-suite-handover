# Politique de sécurité et de contribution

Ne publiez jamais d’identifiant WooCommerce, de Consumer Key, de Consumer Secret, de secret webhook, de mot de passe, de token OAuth, de cookie de navigateur, de base de données ou de fichier `.env` dans ce dépôt.

Toute vulnérabilité ou exposition potentielle doit être documentée sans reproduire de secret. Les changements de sécurité doivent être accompagnés d’un test de régression reproductible, puis validés sur un environnement de staging autorisé avant d’être considérés comme prouvés.

Les contrôles externes — passerelles de paiement, Merchant Center, RUM, appareils physiques, WAF/CDN et pentest — ne doivent pas être simulés. Leur état doit rester explicitement ouvert jusqu’à réception d’une preuve réelle.
