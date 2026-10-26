<!doctype html>
<html lang="de-CH">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="data:," />
    <title>Bildungsplan Grafikerin/Grafiker EFZ</title>
    @vite('src/main.js')
  </head>
  <body>
    {{-- Die eingeloggte Person kommt mit der Seite, damit die Anwendung nicht erst ohne Login erscheint. --}}
    <div
      id="app"
      data-ich="{{ json_encode(auth()->user()?->only('email', 'domain')) }}"
      data-domains="{{ json_encode(\App\Models\Domain::orderBy('name')->pluck('name')) }}"
    ></div>
  </body>
</html>
