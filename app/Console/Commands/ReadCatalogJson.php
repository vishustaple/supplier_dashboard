<?php

namespace App\Console\Commands;

use App\Models\{CatalogItem};
use Illuminate\Console\Command;

class ReadCatalogJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:read-catalog-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /** Sample JSON string */
        $jsonString = '[
            {
                "sku": 347005,
                "breadcrumbs": [
                    "",
                    "Paper",
                    "Copy & Printer Paper",
                    "Copy & Multipurpose Paper"
                ],
                "long_description": "Hammermill\u00ae Copy Plus\u00ae Copy Paper, 10 Reams, White, Letter (8.5\" x 11\"), 5000 Sheets Per Case, 20 Lb, 92 Brightness, FSC\u00ae Certified",
                "detailed_description": "Hammermill Copy Plus paper is an economical copy paper designed for everyday use at offices large and small. Offering dependable performance on all office machines, you\'ll want to have plenty of this dependable paper on hand for everyday, general office use. ColorLok for bolder blacks, brighter colors and faster drying. Backed by the 99.99% Jam-Free Guarantee. Acid-free material prevents yellowing over time to ensure a long-lasting appearance.",
                "specifications": {
                    "Item #": "347005",
                    "Manufacturer #": "105007",
                    "Color": "White",
                    "Sheet Size": "Letter (8-1/2\" x 11\")",
                    "Reams Per Case": "10",
                    "Recommended Paper Use": "General Purpose",
                    "Sheets Per Ream/Pack": "500",
                    "Bleaching Chemistry": "Elemental Chlorine Free (ECF)",
                    "Paper Brightness (US)": "92",
                    "Paper Weight": "20 lb",
                    "Acid Free": "Yes",
                    "Product Line": "Copy Plus",
                    "Colorlok Technology": "Yes",
                    "Number Of Holes Punched": "0",
                    "Brand Name": "Hammermill",
                    "Eco-Conscious": "Leadership Forestry",
                    "Eco Label Standard": "Forest Stewardship Council (FSC) Mixed; SFI Certified Fiber Sourcing",
                    "Manufacturer": "INTERNATIONAL PAPER CO",
                    "Total Number Of Reams": "10",
                    "Total Number Of Sheets": "5000"
                },
                "price": "Not Available",
                "product_link": null,
                "bullet_points": [
                    "Perfect for black and white printing, drafts and forms. - Hammermill is more than just paper.",
                    "99.99% JAM-FREE GUARANTEE - You can trust Hammermill paper quality, guaranteed.",
                    "COLORLOK TECHNOLOGY & ACID-FREE - Colors on Hammermill copy paper are 30% brighter.",
                    "blacks are up to 60% bolder, and inks dry 3 times faster for less smearing. Acid-free Hammermill paper also prevents printing and copier sheets from yellowing over time to ensure long-lasting archival quality.",
                    "RENEWABLE RESOURCE - Hammermill copy paper is Forest Stewardship Council (FSC) certified, contributing to \u201cMR1 Performance\u201d for paper and wood products under LEED.",
                    "Letter-size paper measures 8 1/2\" x 11\" to suit your printing needs.",
                    "Forest Stewardship Council\u00ae (FSC\u00ae) certified \u2014 made from wood/paper that comes from forests managed to rigorous environmental and social standards, supported by the world\'s leading conservation organizations.",
                    "Leadership forestry \u2014 from forests or sourcing programs that meet specific environmental standards, helping you support practices that better protect forests and the environment."
                ]
            },
            {
                "sku": 125420,
                "breadcrumbs": [
                    "",
                    "Paper",
                    "Copy & Printer Paper",
                    "Copy & Multipurpose Paper"
                ],
                "long_description": "Boise\u00ae ASPEN\u00ae 100 Multi-Use Printer & Copy Paper, 10 Reams, White, Letter (8.5\" x 11\"), 5000 Sheets Per Case, 20 Lb, 92 Brightness, 100% Recycled, FSC\u00ae Certified",
                "detailed_description": "Keep your office stocked and ready for your next project with this Boise copy paper. The super-bright construction helps text and images pop, so your documents and fliers draw the attention you need.",
                "specifications": {
                    "Item #": "125420",
                    "Manufacturer #": "054922-CTN",
                    "Color": "White",
                    "Sheet Size": "Letter (8-1/2\" x 11\")",
                    "Reams Per Case": "10",
                    "Recommended Paper Use": "General Purpose",
                    "Sheets Per Ream/Pack": "500",
                    "Bleaching Chemistry": "Elemental Chlorine Free (ECF)",
                    "Finish (Paper)": "Standard",
                    "Paper Brightness (US)": "92",
                    "Paper Weight": "20 lb",
                    "Paper Brightness (Euro)": "104",
                    "Acid Free": "Yes",
                    "Product Line": "Aspen 100 Multi-Use Recycled Copy Paper",
                    "Colorlok Technology": "No",
                    "Number Of Holes Punched": "0",
                    "Brand Name": "BOISE",
                    "Eco-Conscious": "Recycled Content",
                    "Eco Label Standard": "Forest Stewardship Council (FSC) Recycled",
                    "Manufacturer": "BOISE WHITE PAPER, L.L.C.",
                    "Post Consumer Recycled Content Percentage": "100 %",
                    "Total Number Of Reams": "10",
                    "Total Number Of Sheets": "5000",
                    "Total Recycled Content Percentage": "100 %"
                },
                "price": "Not Available",
                "product_link": null,
                "bullet_points": [
                    "20-lb ASPEN multiuse copy paper is great for everyday copies.",
                    "500 sheets per ream help keep your office stocked and ready for printing.",
                    "92 brightness for vivid text and images.",
                    "Acid-free paper resists fading and yellowing over time.",
                    "20-lb paper is ideal for everyday use.",
                    "Elemental Chlorine Free (ECF).",
                    "20-lb paper comes in a ream of 500 sheets. ASPEN multipurpose paper is Boise letter paper that features a professional appearance as well as convenience through machine compatibility.",
                    "Forest Stewardship Council\u00ae (FSC\u00ae) certified \u2014 all the wood or paper in the product comes from reclaimed (re-used) material.",
                    "Contains Recycled Content - See Specs for Details."
                ]
            },
            {
                "sku": 116946,
                "breadcrumbs": [
                    "",
                    "Paper",
                    "Copy & Printer Paper",
                    "Copy & Multipurpose Paper"
                ],
                "long_description": "Boise\u00ae ASPEN\u00ae 30 Multi-Use Printer & Copy Paper, 10 Reams, White, Letter (8.5\" x 11\"), 5000 Sheets Per Case, 20 Lb, 92 Brightness, 30% Recycled, FSC\u00ae Certified",
                "detailed_description": "Great for everyday use, ASPEN 30 Multi-Use copy paper has a Jam-Free\u00ae guarantee which means employees can spend more time on important projects and less time troubleshooting printing issues.",
                "specifications": {
                    "Item #": "116946",
                    "Manufacturer #": "054901-CTN",
                    "Color": "White",
                    "Sheet Size": "Letter (8-1/2\" x 11\")",
                    "Total Number Of Reams": "10",
                    "Reams Per Case": "10",
                    "Recommended Paper Use": "General Purpose",
                    "Sheets Per Ream/Pack": "500",
                    "Bleaching Chemistry": "Elemental Chlorine Free (ECF)",
                    "Finish (Paper)": "Uncoated",
                    "Paper Brightness (US)": "92",
                    "Paper Weight": "20 lb",
                    "Paper Brightness (Euro)": "110",
                    "Acid Free": "Yes",
                    "Product Line": "Aspen 30 Multi-Use Recycled Copy Paper",
                    "Colorlok Technology": "No",
                    "Printer Compatibility": "All-In-One",
                    "Number Of Holes Punched": "0",
                    "Brand Name": "BOISE",
                    "Eco-Conscious": "Leadership Forestry; Recycled Content",
                    "Eco Label Standard": "Forest Stewardship Council (FSC) Mixed",
                    "Manufacturer": "OFFICE DEPOT",
                    "Post Consumer Recycled Content Percentage": "30 %",
                    "Total Number Of Sheets": "5000",
                    "Total Recycled Content Percentage": "30 %"
                },
                "price": "Not Available",
                "product_link": null,
                "bullet_points": [
                    "Go green flawlessly with this recycled paper that maintains the same hardworking characteristics as non-recycled paper. Built to be compatible with office printers and copiers.",
                    "Ideal for interoffice memos and letters.",
                    "Acid-free for archival quality. Great for printing and storing documents for future reference.",
                    "Sales support Boise Project UP\u2122 initiatives. Founded by Boise in 2011, Project UP helps transform undeveloped or abandoned urban spaces into community parks for relaxation, reflection, and rejuvenation. Funded through sales of Aspen recycled paper, Project UP helps create urban green spaces in partnership with the Alliance for Community Trees and is a platinum sponsor of their program, National NeighborWoods\u2122 Month.",
                    "This white ledger paper has 92 brightness.",
                    "20-lb paper is ideal for everyday use.",
                    "Elemental Chlorine Free (ECF).",
                    "Letter paper in a case of 10 reams. Boise ASPEN 30 paper is acid-free copy paper that features a professional appearance as well as convenience through machine compatibility.",
                    "Forest Stewardship Council\u00ae (FSC\u00ae) certified \u2014 made from wood/paper that comes from forests managed to rigorous environmental and social standards, supported by the world\'s leading conservation organizations.",
                    "Leadership forestry \u2014 from forests or sourcing programs that meet specific environmental standards, helping you support practices that better protect forests and the environment.",
                    "Contains Recycled Content - See Specs for Details."
                ]
            },
            {
                "sku": 203132,
                "breadcrumbs": [
                    "",
                    "Cleaning",
                    "Safety & Security",
                    "Head & Face Protection",
                    "Hard Hats & Caps"
                ],
                "long_description": "SKILCRAFT\u00ae Easy Quick-Slide Cap Safety Helmet, Blue (AbilityOne 8415-00-935-3132)",
                "detailed_description": "Easy Quick-Slide sizing adjusts from head sizes 6 1/2 to 8",
                "specifications": {
                    "Item #": "941258",
                    "Manufacturer #": "9353132",
                    "Color": "Blue",
                    "Brand Name": "SKILCRAFT",
                    "Manufacturer": "NATIONAL INDUSTRIES FOR THE BLIND",
                    "Post Consumer Recycled Content Percentage": "0 %"
                },
                "price": "$20.69",
                "product_link": null,
                "bullet_points": [
                    "Molded from high-density polyethylene for durable protection.",
                    "Cap style for a comfortable fit.",
                    "4-point, woven nylon suspension to protect the wearer.",
                    "Meets ANSI Z89.1 - 2009, Type 1, Class E requirements."
                ]
            },
            {
                "sku": 196517,
                "breadcrumbs": [
                    "",
                    "Paper",
                    "Copy & Printer Paper",
                    "Copy & Multipurpose Paper"
                ],
                "long_description": "Boise\u00ae X-9\u00ae Multi-Use Printer & Copy Paper, 10 Reams, White, Letter (8.5\" x 11\"), 5000 Sheets Per Case, 20 Lb, 92 Brightness",
                "detailed_description": "Help projects go smoothly with Boise X-9 multi-use copy paper. Perfect for everyday copying and printing, this multi-use copy paper offers consistent performance and high runnability, so you can worry less about printer jams and downtime.",
                "specifications": {
                    "Item #": "196517",
                    "Manufacturer #": "OX9001-CTN",
                    "Color": "White",
                    "Sheet Size": "Letter (8-1/2\" x 11\")",
                    "Total Number Of Reams": "10",
                    "Reams Per Case": "10",
                    "Recommended Paper Use": "General Purpose",
                    "Sheets Per Ream/Pack": "500",
                    "Bleaching Chemistry": "Elemental Chlorine Free (ECF)",
                    "Finish (Paper)": "Standard",
                    "Paper Brightness (US)": "92",
                    "Paper Weight": "20 lb",
                    "Paper Brightness (Euro)": "104",
                    "Acid Free": "Yes",
                    "Product Line": "X-9 Multi-Use Copy Paper",
                    "Colorlok Technology": "No",
                    "Printer Compatibility": "Laser Printer",
                    "Number Of Holes Punched": "0",
                    "Brand Name": "BOISE",
                    "Eco Label Standard": "SFI Certified Fiber Sourcing",
                    "Manufacturer": "BOISE WHITE PAPER, L.L.C.",
                    "Post Consumer Recycled Content Percentage": "0 %",
                    "Total Number Of Sheets": "5000"
                },
                "price": "Not Available",
                "product_link": null,
                "bullet_points": [
                    "Keep spare paper on hand so you never run out in the middle of a job.",
                    "Ideal for high-volume print jobs when speed matters.",
                    "20lb, 92 Brightness.",
                    "Consistent performance and great runnability.",
                    "Everyday printing and copying.",
                    "99.99% Jam-Free performance guaranteed.",
                    "500 sheets per ream."
                ]
            }
        ]';

        /** Decode JSON to PHP array */
        $products = json_decode($jsonString, true); /** true for associative array */

        /** Accessing elements */
        foreach ($products as $product) {
            CatalogItem::where('sku', trim($product['sku']))
            ->update([
                'catalog_item_url' => $product['product_link'],
                'catalog_item_name' => $product['long_description'],
            ]);
            echo $product['sku']."<br>";
        }

        dd('h');
    }
}
