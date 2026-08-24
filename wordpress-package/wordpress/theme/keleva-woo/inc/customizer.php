<?php
defined('ABSPATH') || exit;

/**
 * Retourne une valeur éditable sans jamais rendre un texte vide sur le storefront.
 */
function keleva_woo_home_copy(string $key, string $default): string {
    $value = get_theme_mod('keleva_home_' . $key, $default);
    $value = is_scalar($value) ? trim((string) $value) : '';
    return $value !== '' ? $value : $default;
}

add_action('customize_register', static function (WP_Customize_Manager $customize): void {
    $customize->add_section('keleva_appearance', [
        'title' => __('Keleva — Apparence', 'keleva-woo'),
        'description' => __('Appliquez une palette à l’ensemble du storefront et aux emails WooCommerce.', 'keleva-woo'),
        'priority' => 20,
    ]);
    $choices = [];
    foreach (keleva_woo_palettes() as $palette_id => $palette) {
        $choices[$palette_id] = $palette['label'];
    }
    $customize->add_setting('keleva_palette', [
        'default' => 'velora',
        'sanitize_callback' => static function ($value) use ($choices): string { $value = sanitize_key((string) $value); return array_key_exists($value, $choices) ? $value : 'velora'; },
        'transport' => 'refresh',
    ]);
    $customize->add_control('keleva_palette', [
        'section' => 'keleva_appearance',
        'label' => __('Palette active', 'keleva-woo'),
        'description' => __('Cinq palettes avec couple texte/fond et CTA contrastés.', 'keleva-woo'),
        'type' => 'select',
        'choices' => $choices,
    ]);

    $sections = [
        'keleva_home_hero' => [
            'title' => __('Keleva — Accueil : hero & catalogue', 'keleva-woo'),
            'description' => __('Modifiez les textes de l’accueil sans Elementor ni JavaScript supplémentaire.', 'keleva-woo'),
            'fields' => [
                'hero_eyebrow' => [__('Surtitre hero', 'keleva-woo'), __('Commerce pensé pour décider', 'keleva-woo'), 'text'],
                'hero_title' => [__('Titre hero — ligne 1', 'keleva-woo'), __('Choisissez vite.', 'keleva-woo'), 'text'],
                'hero_emphasis' => [__('Titre hero — ligne accentuée', 'keleva-woo'), __('Gardez le contrôle.', 'keleva-woo'), 'text'],
                'hero_description' => [__('Description hero', 'keleva-woo'), __('Une boutique pensée pour parcourir moins, décider mieux et garder le panier dans le champ — du premier regard au dernier clic.', 'keleva-woo'), 'textarea'],
                'hero_cta' => [__('Libellé du bouton hero', 'keleva-woo'), __('Explorer la sélection', 'keleva-woo'), 'text'],
                'catalog_eyebrow' => [__('Surtitre catalogue', 'keleva-woo'), __('Le catalogue, sans détour', 'keleva-woo'), 'text'],
                'catalog_title' => [__('Titre catalogue — ligne 1', 'keleva-woo'), __('Objets qui trouvent', 'keleva-woo'), 'text'],
                'catalog_emphasis' => [__('Titre catalogue — ligne accentuée', 'keleva-woo'), __('leur place.', 'keleva-woo'), 'text'],
                'catalog_description' => [__('Description catalogue', 'keleva-woo'), __('Produits simples, options lisibles, aucune distraction inutile.', 'keleva-woo'), 'textarea'],
            ],
        ],
        'keleva_home_benefits' => [
            'title' => __('Keleva — Accueil : bénéfices', 'keleva-woo'),
            'description' => __('Ces contenus n’ajoutent ni widget Elementor ni script front-end.', 'keleva-woo'),
            'fields' => [
                'benefits_eyebrow' => [__('Surtitre bénéfices', 'keleva-woo'), __('Une autre façon de vendre', 'keleva-woo'), 'text'],
                'benefits_title' => [__('Titre bénéfices — ligne 1', 'keleva-woo'), __('Chaque détail est là', 'keleva-woo'), 'text'],
                'benefits_emphasis' => [__('Titre bénéfices — ligne accentuée', 'keleva-woo'), __('pour alléger le choix.', 'keleva-woo'), 'text'],
                'benefit_1_title' => [__('Bénéfice 1 — titre', 'keleva-woo'), __('Un panier qui suit', 'keleva-woo'), 'text'],
                'benefit_1_description' => [__('Bénéfice 1 — texte', 'keleva-woo'), __('On ajuste, on compare et on continue sans perdre la sélection en cours.', 'keleva-woo'), 'textarea'],
                'benefit_2_title' => [__('Bénéfice 2 — titre', 'keleva-woo'), __('Le détail au bon moment', 'keleva-woo'), 'text'],
                'benefit_2_description' => [__('Bénéfice 2 — texte', 'keleva-woo'), __('Le quick view révèle les informations utiles sans imposer un changement de page.', 'keleva-woo'), 'textarea'],
                'benefit_3_title' => [__('Bénéfice 3 — titre', 'keleva-woo'), __('Une fin sans friction', 'keleva-woo'), 'text'],
                'benefit_3_description' => [__('Bénéfice 3 — texte', 'keleva-woo'), __('Le panier et le checkout WooCommerce restent lisibles, courts et adaptés au mobile.', 'keleva-woo'), 'textarea'],
            ],
        ],
        'keleva_home_faq' => [
            'title' => __('Keleva — Accueil : FAQ', 'keleva-woo'),
            'description' => __('Mettez à jour les réponses à vos questions fréquentes sans plugin supplémentaire.', 'keleva-woo'),
            'fields' => [
                'faq_eyebrow' => [__('Surtitre FAQ', 'keleva-woo'), __('Questions fréquentes', 'keleva-woo'), 'text'],
                'faq_title' => [__('Titre FAQ — ligne 1', 'keleva-woo'), __('Tout ce qu’il faut', 'keleva-woo'), 'text'],
                'faq_emphasis' => [__('Titre FAQ — ligne accentuée', 'keleva-woo'), __('avant de décider.', 'keleva-woo'), 'text'],
                'faq_1_question' => [__('FAQ 1 — question', 'keleva-woo'), __('Pourquoi le quick view ?', 'keleva-woo'), 'text'],
                'faq_1_answer' => [__('FAQ 1 — réponse', 'keleva-woo'), __('Il permet de consulter les informations clés et d’ajouter un produit sans quitter le catalogue. Les produits restaurant conservent leur fiche dédiée pour choisir les sauces.', 'keleva-woo'), 'textarea'],
                'faq_2_question' => [__('FAQ 2 — question', 'keleva-woo'), __('Le checkout est-il adapté au mobile ?', 'keleva-woo'), 'text'],
                'faq_2_answer' => [__('FAQ 2 — réponse', 'keleva-woo'), __('Oui. Le panier reste accessible, les contrôles sont dimensionnés pour le pouce et le checkout reste celui de WooCommerce.', 'keleva-woo'), 'textarea'],
                'faq_3_question' => [__('FAQ 3 — question', 'keleva-woo'), __('Les images sont-elles optimisées ?', 'keleva-woo'), 'text'],
                'faq_3_answer' => [__('FAQ 3 — réponse', 'keleva-woo'), __('Les sources AVIF et WebP sont privilégiées lorsque le navigateur les accepte, avec une image de repli compatible.', 'keleva-woo'), 'textarea'],
            ],
        ],
    ];

    foreach ($sections as $section_id => $section) {
        $customize->add_section($section_id, [
            'title' => $section['title'],
            'description' => $section['description'],
            'priority' => 35,
        ]);
        foreach ($section['fields'] as $key => [$label, $default, $type]) {
            $setting = 'keleva_home_' . $key;
            $customize->add_setting($setting, [
                'default' => $default,
                'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
                'transport' => 'refresh',
            ]);
            $customize->add_control($setting, [
                'section' => $section_id,
                'label' => $label,
                'type' => $type,
            ]);
        }
    }

    $customize->add_setting('keleva_home_hero_image_id', [
        'default' => '0',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ]);
    $customize->add_control(new WP_Customize_Media_Control($customize, 'keleva_home_hero_image_id', [
        'section' => 'keleva_home_hero',
        'label' => __('Image hero décorative', 'keleva-woo'),
        'description' => __('Choisissez le visuel éditorial affiché dans le hero. Si ce champ est vide, le premier produit décoratif est utilisé.', 'keleva-woo'),
        'mime_type' => 'image',
    ]));
});
