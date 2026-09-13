<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Statistics\MarketingIdeas;
use Illuminate\Contracts\View\View;

class AdminMarketingController extends Controller
{
    public function __invoke(MarketingIdeas $ideas): View
    {
        $data = $ideas->build();

        $format = fn (array $rows) => collect($rows)->map(fn ($row, $i) => ($i + 1).'. '.$row['name'].' – '.format_distance($row['median_distance_meters']).' ernaast')->join("\n");

        $captions = [
            'hardest' => count($data['hardest']) ? "De vijf stations waar Nederland deze week het verst naast zat 🚆\n\n".$format($data['hardest'])."\n\nWeet jij ze wél te vinden? Speel mee via de link in bio.\n\n#treinprikker #treinen #spoor #nederland #topografie #dagelijksspel" : null,
            'easiest' => count($data['easiest']) ? "Deze vijf stations kent iedereen blijkbaar 🎯\n\n".$format($data['easiest'])."\n\nMorgen weer vijf nieuwe. Link in bio.\n\n#treinprikker #treinen #spoor #nederland #topografie" : null,
            'teaser' => count($data['today']) ? 'Station '.count($data['today']).' van 5 vandaag: waar ligt '.$data['today'][count($data['today']) - 1]['name']."?\n\nPrik 'm op treinprikker.nl (link in bio). Antwoord morgen in onze story.\n\n#treinprikker #treinen #spoor" : null,
            'misplaced' => $data['misplaced'] && $data['misplaced']['description']
                ? format_number($data['misplaced']['guess_count']).' mensen prikten '.$data['misplaced']['name'].'. Gemiddeld '.$data['misplaced']['description'].". Weet jij het beter?\n\n#treinprikker #treinen #spoor #nederland"
                : null,
            'carousel' => "Zo werkt Treinprikker 🚆\n\n1. Elke dag vijf stations, voor iedereen dezelfde.\n2. Prik op de kaart waar jij denkt dat het ligt. Alleen de naam, geen hints.\n3. Hoe dichterbij, hoe meer punten: max 1000 per station, 5000 per dag.\n\nGratis, geen account. Link in bio.\n\n#treinprikker #treinen #spoor #nederland #dagelijksspel",
        ];

        return view('admin.marketing', ['data' => $data, 'captions' => $captions]);
    }
}
