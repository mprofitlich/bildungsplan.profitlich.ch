<!doctype html>
<html lang="de-CH">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <link rel="icon" href="data:," />
    <title>Einloggen – {{ config('app.name') }}</title>
    @vite('src/main.js')
  </head>
  <body>
    <main class="login-seite">
      <h1>{{ config('app.name') }}</h1>
      @if ($gueltig)
        <form method="post" action="{{ route('login.einloesen', $token) }}">
          @csrf
          <button type="submit" autofocus>Einloggen</button>
        </form>
      @else
        <p>Dieser Link ist abgelaufen oder wurde schon verwendet.</p>
        <p><a href="/">Zur Anwendung</a> – dort lässt sich ein neuer Link anfordern.</p>
      @endif
    </main>
  </body>
</html>
