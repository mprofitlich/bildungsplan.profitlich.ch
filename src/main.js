import { mount } from 'svelte';
import '@fontsource-variable/dm-sans';
import App from './App.svelte';
import { starten } from './lib/abgleich.svelte.js';
import { konto } from './lib/konto.svelte.js';
import './app.css';

// Die Bestätigungsseite des Login-Links nutzt nur die Gestaltung, nicht die Anwendung.
const ziel = document.getElementById('app');

// Mit Login erscheint die Anwendung erst mit den Kursen vom Server, nicht zuerst mit denen des Browsers.
if (ziel && konto.ich) await starten();
if (ziel) mount(App, { target: ziel });
