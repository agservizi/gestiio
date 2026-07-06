<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MarketingController extends Controller
{
    public function home()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore'])) {
                return redirect('/backend');
            }

            return redirect('/area-personale');
        }

        return view('Frontend.Marketing.home', $this->payload(
            'Gestiio AG SERVIZI | Portale agenti e collaboratori',
            'Collabora con AG SERVIZI: registrati come agente e gestisci pratiche, contratti, ticket, documenti e plafond da un unico portale.',
            url('/')
        ));
    }

    public function collabora()
    {
        return view('Frontend.Marketing.collabora', $this->payload(
            'Collabora con AG SERVIZI | Registrazione agente Gestiio',
            'Scopri come entrare nella rete AG SERVIZI e usare Gestiio per lavorare su contratti, servizi, ticket, documenti, spedizioni e plafond.',
            url('/collabora-con-ag-servizi')
        ));
    }

    private function payload(string $title, string $description, string $canonical): array
    {
        return [
            'metaTitle' => $title,
            'metaDescription' => $description,
            'canonicalUrl' => $canonical,
            'schema' => $this->schema($title, $description, $canonical),
        ];
    }

    private function schema(string $title, string $description, string $canonical): array
    {
        $siteUrl = url('/');
        $registerUrl = url('/register');
        $logoUrl = url('/loghi/logo.png');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $siteUrl.'#organization',
                    'name' => 'AG SERVIZI',
                    'url' => $siteUrl,
                    'logo' => $logoUrl,
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => 'Gestiio',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $siteUrl.'#website',
                    'name' => 'Gestiio',
                    'url' => $siteUrl,
                    'publisher' => [
                        '@id' => $siteUrl.'#organization',
                    ],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => $siteUrl.'#software',
                    'name' => 'Gestiio',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'url' => $siteUrl,
                    'description' => $description,
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => $registerUrl,
                        'price' => '0',
                        'priceCurrency' => 'EUR',
                        'availability' => 'https://schema.org/InStock',
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $canonical.'#webpage',
                    'url' => $canonical,
                    'name' => $title,
                    'description' => $description,
                    'isPartOf' => [
                        '@id' => $siteUrl.'#website',
                    ],
                    'about' => [
                        '@id' => $siteUrl.'#software',
                    ],
                    'inLanguage' => 'it-IT',
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $canonical.'#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Home',
                            'item' => $siteUrl,
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => $canonical === $siteUrl ? 'Portale agenti' : 'Collabora con AG SERVIZI',
                            'item' => $canonical,
                        ],
                    ],
                ],
            ],
        ];
    }
}
