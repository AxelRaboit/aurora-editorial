export const TEMPLATES = [
    {
        id: "blank",
        category: "layout",
        icon: "⬜",
        blocks: [],
    },
    {
        id: "article",
        category: "article",
        icon: "📄",
        blocks: [
            {
                type: "header",
                data: {
                    text: "Comment améliorer votre flux de travail en 2024",
                    level: 2,
                },
            },
            {
                type: "paragraph",
                data: {
                    text: "Dans un monde où la productivité est reine, il est essentiel de trouver les bons outils et méthodes pour rester efficace au quotidien.",
                },
            },
            {
                type: "image",
                data: {
                    file: { url: "https://picsum.photos/seed/article/800/450" },
                    caption: "Photo d'illustration",
                    withBorder: false,
                    stretched: false,
                    withBackground: false,
                },
            },
            {
                type: "paragraph",
                data: {
                    text: "Les équipes les plus performantes partagent un point commun : elles ont su adopter des processus clairs et des outils adaptés à leurs besoins.",
                },
            },
            {
                type: "paragraph",
                data: {
                    text: "En mettant en place ces pratiques dès aujourd'hui, vous constaterez rapidement une amélioration notable de votre efficacité collective.",
                },
            },
        ],
    },
    {
        id: "journal",
        category: "article",
        icon: "📰",
        blocks: [
            {
                type: "header",
                data: { text: "La révolution du travail hybride", level: 2 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Le monde du travail a profondément évolué ces dernières années. Voici comment les entreprises et les individus s'adaptent à cette nouvelle réalité.",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "mediaText",
                data: {
                    url: "https://picsum.photos/seed/journal1/600/400",
                    caption: "",
                    text: "Le télétravail a profondément transformé notre façon de collaborer. Les entreprises repensent leurs espaces et leurs modes de management pour s'adapter à cette nouvelle réalité.",
                    flip: false,
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "mediaText",
                data: {
                    url: "https://picsum.photos/seed/journal2/600/400",
                    caption: "",
                    text: "Les bureaux repensent leur rôle dans cet écosystème hybride. Ils deviennent des espaces de collaboration et de créativité plutôt que de simples postes de travail.",
                    flip: true,
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "mediaText",
                data: {
                    url: "https://picsum.photos/seed/journal3/600/400",
                    caption: "",
                    text: "Les outils numériques jouent un rôle central dans cette transformation. Messagerie instantanée, visioconférence, espaces de travail partagés — la boîte à outils s'est considérablement enrichie.",
                    flip: false,
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "mediaText",
                data: {
                    url: "https://picsum.photos/seed/journal4/600/400",
                    caption: "",
                    text: "L'équilibre vie professionnelle et personnelle reste le défi majeur. Les organisations qui réussissent sont celles qui font confiance à leurs équipes et mesurent les résultats plutôt que le temps de présence.",
                    flip: true,
                },
            },
        ],
    },
    {
        id: "twoColumn",
        category: "layout",
        icon: "▥",
        blocks: [
            {
                type: "header",
                data: { text: "Avantages & Inconvénients", level: 2 },
            },
            {
                type: "twoColumn",
                data: {
                    left: "✓ Flexibilité accrue\n✓ Meilleure concentration\n✓ Gain de temps sur les trajets",
                    right: "✗ Isolement social possible\n✗ Frontières vie pro/perso floues\n✗ Dépendance aux outils numériques",
                },
            },
        ],
    },
    {
        id: "landing",
        category: "marketing",
        icon: "🚀",
        blocks: [
            {
                type: "header",
                data: {
                    text: "Transformez votre activité dès aujourd'hui",
                    level: 2,
                },
            },
            {
                type: "paragraph",
                data: {
                    text: "Une solution complète pour les équipes modernes qui souhaitent aller plus loin dans leur organisation et leur collaboration.",
                },
            },
            {
                type: "image",
                data: {
                    file: {
                        url: "https://picsum.photos/seed/landing/1200/500",
                    },
                    caption: "",
                    withBorder: false,
                    stretched: true,
                    withBackground: false,
                },
            },
            {
                type: "header",
                data: { text: "Tout ce dont vous avez besoin", level: 3 },
            },
            {
                type: "list",
                data: {
                    style: "unordered",
                    items: [
                        "Interface intuitive et moderne",
                        "Collaboration en temps réel",
                        "Intégrations avec vos outils existants",
                    ],
                },
            },
            {
                type: "callout",
                data: {
                    type: "tip",
                    title: "Essai gratuit",
                    message:
                        "Commencez votre essai gratuit de 14 jours, sans carte bancaire requise.",
                },
            },
        ],
    },
    {
        id: "tutorial",
        category: "technique",
        icon: "🛠️",
        blocks: [
            {
                type: "header",
                data: {
                    text: "Guide : Configurer votre environnement",
                    level: 2,
                },
            },
            {
                type: "paragraph",
                data: {
                    text: "Dans ce tutoriel, nous allons mettre en place un environnement de développement complet, étape par étape.",
                },
            },
            {
                type: "callout",
                data: {
                    type: "info",
                    title: "Prérequis",
                    message:
                        "Assurez-vous d'avoir Node.js 18+ et npm installés sur votre machine avant de commencer.",
                },
            },
            {
                type: "header",
                data: { text: "Étape 1 : Installation", level: 3 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Commencez par cloner le dépôt et installer les dépendances nécessaires.",
                },
            },
            {
                type: "code",
                data: {
                    code: "git clone https://github.com/votre-projet\ncd votre-projet\nnpm install",
                },
            },
            {
                type: "header",
                data: { text: "Étape 2 : Configuration", level: 3 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Créez un fichier .env à la racine du projet et renseignez vos variables d'environnement.",
                },
            },
            {
                type: "code",
                data: {
                    code: "cp .env.example .env\n# Modifiez les variables selon votre configuration",
                },
            },
            {
                type: "callout",
                data: {
                    type: "success",
                    title: "C'est prêt !",
                    message:
                        "Lancez npm run dev pour démarrer le serveur de développement sur http://localhost:5173",
                },
            },
        ],
    },
    {
        id: "newsletter",
        category: "marketing",
        icon: "✉️",
        blocks: [
            {
                type: "header",
                data: { text: "📬 Lettre du mois — Décembre 2024", level: 2 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Bonjour ! Voici un résumé des actualités et ressources sélectionnées pour vous ce mois-ci. Bonne lecture !",
                },
            },
            {
                type: "image",
                data: {
                    file: {
                        url: "https://picsum.photos/seed/newsletter-hero/800/300",
                    },
                    caption: "",
                    withBorder: false,
                    stretched: true,
                    withBackground: false,
                },
            },
            { type: "delimiter", data: {} },
            { type: "header", data: { text: "🔥 À la une", level: 3 } },
            {
                type: "list",
                data: {
                    style: "unordered",
                    items: [
                        "Nouvelle fonctionnalité : tableaux de bord personnalisés",
                        "Mise à jour de sécurité importante — pensez à mettre à jour",
                        "Webinaire exclusif le 15 janvier — inscrivez-vous vite",
                    ],
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "header",
                data: { text: "📖 Lecture recommandée", level: 3 },
            },
            {
                type: "mediaText",
                data: {
                    url: "https://picsum.photos/seed/newsletter-read/400/300",
                    caption: "",
                    text: "Cette semaine, nous avons adoré cet article sur les bonnes pratiques de design system. Une lecture incontournable pour toute l'équipe.",
                    flip: false,
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "callout",
                data: {
                    type: "tip",
                    title: "Conseil du mois",
                    message:
                        "Pensez à archiver vos anciens projets pour garder votre espace de travail organisé et performant.",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "paragraph",
                data: {
                    text: "Merci de votre fidélité. À très bientôt !\n— L'équipe",
                },
            },
        ],
    },
    {
        id: "changelog",
        category: "technique",
        icon: "📝",
        blocks: [
            {
                type: "header",
                data: { text: "Changelog — v1.0.0", level: 2 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Date de sortie : [date]. Résumé des changements apportés dans cette version.",
                },
            },
            {
                type: "callout",
                data: {
                    type: "success",
                    title: "Nouveautés",
                    message:
                        "— Fonctionnalité A ajoutée\n— Fonctionnalité B ajoutée",
                },
            },
            {
                type: "callout",
                data: {
                    type: "tip",
                    title: "Améliorations",
                    message:
                        "— Performance de X améliorée\n— Interface de Y simplifiée",
                },
            },
            {
                type: "callout",
                data: {
                    type: "warning",
                    title: "Corrections",
                    message:
                        "— Correction du bug #123\n— Correction du bug #456",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "header",
                data: { text: "Changelog — v0.9.0", level: 3 },
            },
            {
                type: "list",
                data: {
                    style: "unordered",
                    items: [
                        "Première version publique",
                        "Mise en place de l'architecture de base",
                        "Intégration des outils principaux",
                    ],
                },
            },
        ],
    },
    {
        id: "faq",
        category: "technique",
        icon: "❓",
        blocks: [
            {
                type: "header",
                data: { text: "Questions fréquentes", level: 2 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Retrouvez ci-dessous les réponses aux questions les plus courantes.",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "header",
                data: { text: "Comment commencer ?", level: 3 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Il suffit de créer un compte et de suivre les étapes d'intégration. Le processus prend moins de 5 minutes.",
                },
            },
            {
                type: "header",
                data: { text: "Quels sont les prérequis ?", level: 3 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Aucun prérequis technique particulier. Une connexion internet et un navigateur récent suffisent.",
                },
            },
            {
                type: "header",
                data: { text: "Comment contacter le support ?", level: 3 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Notre équipe est disponible par email et par chat en direct du lundi au vendredi, de 9h à 18h.",
                },
            },
            {
                type: "callout",
                data: {
                    type: "info",
                    title: "Vous ne trouvez pas votre réponse ?",
                    message:
                        "Consultez notre documentation complète ou contactez notre équipe support.",
                },
            },
        ],
    },
    {
        id: "portfolio",
        category: "marketing",
        icon: "🎨",
        blocks: [
            {
                type: "header",
                data: { text: "Nom du projet", level: 2 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Une courte accroche qui résume l'essence du projet en une ou deux phrases percutantes.",
                },
            },
            {
                type: "image",
                data: {
                    file: {
                        url: "https://picsum.photos/seed/portfolio/1200/600",
                    },
                    caption: "Aperçu du projet",
                    withBorder: false,
                    stretched: true,
                    withBackground: false,
                },
            },
            {
                type: "header",
                data: { text: "À propos du projet", level: 3 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Décrivez ici le contexte, les objectifs et les défis relevés. Expliquez votre démarche et les choix réalisés tout au long du projet.",
                },
            },
            {
                type: "twoColumn",
                data: {
                    left: "🎯 Objectifs\n— Définir la cible\n— Répondre au besoin\n— Livrer dans les délais",
                    right: "🛠️ Technologies\n— Outil A\n— Outil B\n— Outil C",
                },
            },
            {
                type: "callout",
                data: {
                    type: "success",
                    title: "Résultat",
                    message:
                        "Décrivez ici l'impact concret du projet : chiffres clés, retours clients, bénéfices mesurables.",
                },
            },
        ],
    },
    {
        id: "comparison",
        category: "layout",
        icon: "⚖️",
        blocks: [
            {
                type: "header",
                data: { text: "Comparatif : Option A vs Option B", level: 2 },
            },
            {
                type: "paragraph",
                data: {
                    text: "Voici une comparaison détaillée pour vous aider à choisir la solution la mieux adaptée à votre situation.",
                },
            },
            {
                type: "twoColumn",
                data: {
                    left: "✅ Option A\n— Avantage 1\n— Avantage 2\n— Avantage 3",
                    right: "✅ Option B\n— Avantage 1\n— Avantage 2\n— Avantage 3",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "twoColumn",
                data: {
                    left: "❌ Inconvénients A\n— Limite 1\n— Limite 2",
                    right: "❌ Inconvénients B\n— Limite 1\n— Limite 2",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "callout",
                data: {
                    type: "tip",
                    title: "Notre recommandation",
                    message:
                        "Résumez ici votre verdict final et les critères décisifs pour orienter le choix du lecteur.",
                },
            },
        ],
    },
    {
        id: "recap",
        category: "article",
        icon: "📋",
        blocks: [
            {
                type: "header",
                data: {
                    text: "Compte-rendu — [Événement / Réunion]",
                    level: 2,
                },
            },
            {
                type: "paragraph",
                data: {
                    text: "Résumé de la session du [date]. Participants : [liste]. Durée : [durée].",
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "header",
                data: { text: "Points abordés", level: 3 },
            },
            {
                type: "list",
                data: {
                    style: "unordered",
                    items: [
                        "Point 1 — description courte",
                        "Point 2 — description courte",
                        "Point 3 — description courte",
                    ],
                },
            },
            {
                type: "header",
                data: { text: "Décisions prises", level: 3 },
            },
            {
                type: "list",
                data: {
                    style: "ordered",
                    items: ["Décision 1", "Décision 2", "Décision 3"],
                },
            },
            { type: "delimiter", data: {} },
            {
                type: "callout",
                data: {
                    type: "info",
                    title: "Prochaines étapes",
                    message:
                        "Listez ici les actions à suivre, les responsables et les échéances associées.",
                },
            },
        ],
    },
];
