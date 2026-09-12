import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import 'maplibre-gl/dist/maplibre-gl.css';
import gameMap from './game-map';
import stationsMap from './stations-map';
import shareResult from './share-result';

Alpine.data('gameMap', gameMap);
Alpine.data('stationsMap', stationsMap);
Alpine.data('shareResult', shareResult);

// The game screen locks body scrolling; release it once the result screen shows.
window.addEventListener('game-finished', () => document.body.classList.remove('game-screen'));

Livewire.start();
