# Projet — Vue d'ensemble des pages (ViewProjectM)

> Document décrivant chaque vue du projet Loup-Garou Companion Platform.
> Theme : dark fantasy / gothique — fond noir profond, accents dorés, ambiance tamisée.

---

## 1. Design System Global

### Palette de couleurs (Tailwind custom)

| Token CSS | Hex | Usage |
|---|---|---|
| `--color-bg-primary` | `#0D0D0D` | Fond principal (noir profond) |
| `--color-bg-surface` | `#1A1510` | Fond cartes/panneaux |
| `--color-bg-elevated` | `#251E16` | Inputs, surfaces secondaires, hover |
| `--color-text-primary` | `#E8D9B5` | Texte principal (parchemin chaud) |
| `--color-text-secondary` | `#9A8A6A` | Texte atténué / labels (doré sombre) |
| `--color-accent-warm` | `#C8922A` | Accent doré — boutons, bordures, surbrillances |
| `--color-accent-danger` | `#8B2020` | Danger, mort, erreurs |
| `--color-accent-village` | `#3A6B3A` | Village / succès (vert) |
| `--color-accent-neutral` | `#5A5A8A` | Info neutre (bleu-gris) |
| `--color-accent-lovers` | `#8B4A6B` | Amoureux (rose) |
| `--color-masked-card` | `#000000` | Carte rôle masquée |
| `--color-dead-player` | `#3A3530` | Joueur mort (atténué) |

### Typographie

| Usage | Police | Poids |
|---|---|---|
| Titres, jeux | `Cinzel` (serif) | 400, 600, 700 |
| Corps de texte | `Inter` (sans-serif) | 300, 400, 500, 600 |

### Calques atmosphériques (fixes, pointer-events-none)

- **`.fog-layer`** : Lueur dorée en bas de l'écran (radial-gradient)
- **`.vignette`** : Vignette sombre sur les bords (opacité 0.6)
- Superposition : `.fog-layer` (z-index:0) → `.vignette` (z-index:1) → contenu (z-index:10)

### Animations CSS clés

| Animation | Durée | Description |
|---|---|---|
| `phaseOverlayIn` | 0.6s | Fade in (30%) → hold (70%) → fade out |
| `fogDrift` | — | Translation X/Y lente avec oscillation d'opacité |
| `candleFlicker` | — | Oscillation d'opacité (effet flamme) |
| `pulseGlow` | — | Pulsation de box-shadow dorée |
| `elementFadeIn` | — | Fade in + translation Y vers le haut |
| `bellToll` | — | Scale bounce + fade out (notification) |

---

## 2. Layout principal — `layouts/app.blade.php`

**Fichier :** `resources/views/layouts/app.blade.php`
**Rôle :** Coque HTML de base pour toutes les pages.

**Structure :**
- `<html class="dark">` — Dark mode forcé
- Google Fonts : `Cinzel` + `Inter`
- `@vite(['resources/css/app.css', 'resources/js/app.js'])`
- `@livewireStyles` / `@livewireScripts`
- Calques atmosphériques avant le contenu
- `<main class="flex-1">` avec `{{ $slot }}` + `@yield('content')`

**Espace écran :** Full-screen (`min-h-screen flex flex-col`), contenu scrollable.
**Dépendances :** Vite, Livewire.

---

## 3. Pages publiques (pré-jeu)

### 3.1 `welcome.blade.php` — Page d'accueil

**Chemin :** `resources/views/welcome.blade.php`
**Route :** `GET /` (nom : `home`)
**Rôle :** Point d'entrée. Choix de la langue (EN/FR) et navigation vers création ou rejoindre un salon.

**Thème :**
- Pleine page centrée : `min-h-screen flex flex-col items-center justify-center`
- Conteneur : `max-w-sm mx-auto text-center`
- Titre : `font-serif text-4xl text-[#E8D9B5]`
- Sous-titre : `text-[#9A8A6A] text-sm`
- CTA principal : `bg-[#C8922A] text-[#0D0D0D]` (doré sur fond sombre)
- CTA secondaire : `border border-[#C8922A] text-[#C8922A]` (outline doré)
- Toggle locale : position absolute top-right, actif `bg-[#C8922A]`

**Composants :** Aucun Livewire (Blade statique).
**Espace écran :** ~40% vertical centré, max 384px de large.

**États :**
- Normal : deux boutons visibles
- Locale active : EN ou FR surbrillant

**Responsive :** Mobile-first, centré, padding `px-6`.

---

### 3.2 `livewire/lobby/create-room.blade.php` — Créer un salon

**Chemin :** `resources/views/livewire/lobby/create-room.blade.php`
**Livewire :** `App\Livewire\Lobby\CreateRoom`
**Route :** `GET /create` (nom : `rooms.create`)
**Rôle :** Formulaire de création de salon : saisie du pseudo → création → redirection vers le salon narrateur.

**Thème :**
- Pleine page centrée : `max-w-sm mx-auto`
- Input : `bg-[#1A1510] border border-[#251E16] text-[#E8D9B5] focus:border-[#C8922A]`
- Erreur : `text-[#8B2020] text-sm`
- Bouton submit : `bg-[#C8922A] text-[#0D0D0D]`

**États :**
- Normal : formulaire vide
- Erreur : `@error('nickname')` — texte rouge sous l'input
- Succès : dispatch `room-created` → redirection JS `window.location.href`

**Espace écran :** Full-page centré, ~30% vertical.

---

### 3.3 `livewire/lobby/join-room.blade.php` — Rejoindre un salon

**Chemin :** `resources/views/livewire/lobby/join-room.blade.php`
**Livewire :** `App\Livewire\Lobby\JoinRoom`
**Route :** `GET /join/{code?}` (nom : `rooms.join`)
**Rôle :** Formulaire pour rejoindre un salon : code 6 caractères + pseudo.

**Thème :** Identique à create-room, avec :
- Input code : `text-center text-2xl tracking-[0.5em] uppercase` — large, espacement large
- `maxlength="6"` pour le code, `maxlength="30"` pour le pseudo

**États :**
- Normal, Erreur (pseudo, code invalide), Succès (dispatch `room-joined`)

**Espace écran :** Full-page centré.

---

## 4. Pages joueur

### 4.1 `livewire/player/player-lobby.blade.php` — Salon d'attente joueur

**Chemin :** `resources/views/livewire/player/player-lobby.blade.php`
**Livewire :** `App\Livewire\Player\PlayerLobby`
**Route :** `GET /room/{room}/player` (nom : `lobby.player`)
**Rôle :** Écran affiché après qu'un joueur a rejoint. Affiche le code du salon, son pseudo, la liste des joueurs connectés et une animation d'attente.

**Thème :**
- Pleine page centrée : `min-h-screen flex flex-col items-center justify-center`
- Code salon : `font-mono text-2xl tracking-[0.3em] text-[#C8922A] font-bold`
- Pseudo joueur : `font-serif text-2xl text-[#C8922A]`
- Liste joueurs : via composant partagé
- Animation d'attente : 3 points pulsés `bg-[#C8922A]` avec `animate-pulse` et `animation-delay-200/400`

**Composants utilisés :**
- `<livewire:shared.player-list :room="$room">`

**États :**
- Normal : affiche code, pseudo, liste, animation
- Écoute `GameStarted` (redirect vers `game.player`)

**Espace écran :** Full-page centré, max `max-w-sm`, pas de scroll (sauf si longue liste).

---

### 4.2 `livewire/player/player-game-view.blade.php` — Vue de jeu joueur

**Chemin :** `resources/views/livewire/player/player-game-view.blade.php`
**Livewire :** `App\Livewire\Player\PlayerGameView`
**Route :** `GET /game/{room}/player` (nom : `game.player`)
**Rôle :** Vue maître orchestrant toutes les phases de jeu côté joueur. C'est la vue la plus complexe côté joueur.

**Thème :**
- Full-screen : `min-h-screen flex flex-col items-center justify-center p-8 select-none`
- Polling : `wire:poll.10s="pollGameState"`
- Indicateur de phase : round + nom de phase centré en haut

**États :** (6 phases gérées)

| Phase | Affichage |
|---|---|
| `waiting` | Role card + bouton "I'm ready!" / checkmark vert si prêt |
| `night` | Role card + composant NightAction |
| `day` | Role card + infos résultats (Seer/Fox/Lover) + zone discussion |
| `voting` | Role card + composant VotingPanel |
| `finished` | Écran game over : gagnant + tous les rôles révélés |
| `is_alive === false` | Message "You are dead" (rouge) |

**Sous-composants intégrés :**
- `<livewire:player.role-card>`
- `<livewire:player.night-action>`
- `<livewire:player.voting-panel>`

**Overlays et notifications :**

1. **Phase transition overlay** (z-50) :
   - Fixed full-screen, fond radial-gradient selon phase
   - Animation : `transition-all duration-700` (entrée), `duration-500` (sortie)
   - Texte : `text-4xl font-serif font-bold`
   - Auto-dismiss : 1500ms

2. **Result notification popup** (z-50, top-4, center) :
   - `max-w-md w-full`, bordure colorée selon type
   - Types : `seer` (doré), `fox` (doré), `night_resolved` (rouge), `lover_died` (rouge), `village_idiot` (doré), `lovers_revealed` (rose), `narrator_msg` (bleu)
   - Auto-dismiss : 6s

3. **Night resolved notification** (z-40, top-24, center) :
   - Liste des éliminés / aucun mort
   - Bordure : `border-[#8B2020]`
   - Auto-dismiss : 8s

**Éléments masqués (hold-to-reveal) :**
- Résultats Seer/Fox : masqués par défaut ("Discussion Time") → révélés au touché
- Info amoureux : masquée par défaut → révélée au touché
- Utilise `x-data="{ revealed: false }"` avec events mousedown/touchstart

**États spéciaux :**
- Dernières morts de la nuit : bandeau rouge visible en phase day/voting
- Joueur mort : message rouge fixe, pas de panneau d'action

**Espace écran :** Full-page scrollable, sous-composants max `max-w-md`.

**Événements WebSocket écoutés :**
- `PhaseChanged`, `PlayerEliminated`, `NightResolved`, `LoverDied`, `VillageIdiotRevealed`, `GameFinished`, `GameReset`
- `RoleAssigned`, `SeerResultReady`, `FoxResultReady`, `NarratorMessageSent`

---

### 4.3 `livewire/player/role-card.blade.php` — Carte Rôle

**Chemin :** `resources/views/livewire/player/role-card.blade.php`
**Livewire :** `App\Livewire\Player\RoleCard`
**Rôle :** Carte de rôle avec système hold-to-reveal (masquer/révéler par pression).

**Thème :**
- Taille fixe : `w-64 h-96` (256 × 384 px)
- **Face masquée :** `bg-gradient-to-br from-[#1A1510] to-[#0D0A07] border-2 border-[#C8922A]/30`
  - "?" géant : `text-5xl text-[#C8922A]/40`
  - Texte : "HOLD TO REVEAL" en `text-[#9A8A6A] text-sm tracking-widest uppercase`
- **Face révélée :** `bg-gradient-to-br from-[#2A2015] to-[#1A1510] border-2 border-[#C8922A] p-6`
  - Faction : `text-[#9A8A6A] text-xs uppercase`
  - Nom rôle : `text-[#E8D9B5] text-2xl font-bold`
  - Description : `text-[#9A8A6A] text-sm`
  - Ordre nuit : `text-[#C8922A] text-xs` (si applicable)

**Interactivité :** Alpine.js `x-data="{ revealed: false }"` avec :
- `x-on:mousedown` / `x-on:mouseup` / `x-on:mouseleave`
- `x-on:touchstart` / `x-on:touchend`
- `[x-cloak]` sur la face révélée

**Espace écran :** Carte centrée (256×384px), ne prend qu'une fraction de l'écran.

---

### 4.4 `livewire/player/night-action.blade.php` — Panneau d'action nocturne

**Chemin :** `resources/views/livewire/player/night-action.blade.php`
**Livewire :** `App\Livewire\Player\NightAction`
**Rôle :** Panneau de soumission des actions de nuit. Gère la sélection de cible, la confirmation, et l'affichage post-soumission (réel ou leurre).

**Thème :**
- Conteneur : `bg-[#1A1510] border border-[#251E16] rounded-xl p-6 w-full max-w-md`
- Boutons cibles : `bg-[#251E16] text-[#E8D9B5] hover:bg-[#3A3530]`
- Liste scrollable : `space-y-2 max-h-64 overflow-y-auto`
- Bouton confirmation : `bg-[#3A6B3A] text-[#E8D9B5]` (vert)
- Bouton annulation : `bg-[#251E16] text-[#9A8A6A]`

**États :** (5 états)

| État | Description |
|---|---|
| `Sélection initiale` | Liste des joueurs vivants (hors soi-même) |
| `Cupid step 0` | Sélection du premier amoureux |
| `Cupid step 1` | Sélection du second amoureux (excluant le premier) |
| `Confirmation` | Affiche la cible choisie + boutons Cancel/Confirm |
| `Soumis (réel)` | Checkmark + hold-to-reveal pour voir l'action |
| `Soumis (leurre)` | Checkmark + hold-to-reveal pour voir le texte leurre |

**Cas spécial Cupid :** Deux cibles affichées avec `&` séparateur.

**Espace écran :** Carte `max-w-md` avec scroll interne `max-h-64` pour la liste.

---

### 4.5 `livewire/player/voting-panel.blade.php` — Panneau de vote

**Chemin :** `resources/views/livewire/player/voting-panel.blade.php`
**Livewire :** `App\Livewire\Player\VotingPanel`
**Rôle :** Panneau de vote avec tally en direct, sélection de cible, confirmation, et gestion des bans.

**Thème :**
- Conteneur : `bg-[#1A1510] border border-[#251E16] rounded-xl p-6 w-full max-w-md`
- Live tally : `bg-[#251E16]/50 rounded text-sm` — nom + compteur doré
- Boutons cibles : identique night-action
- Bouton confirmation vote : `bg-[#5C2A1A] text-[#E8A88A]` (rouge-brun)
- État banni : `text-[#8B2020]` centré

**États :**

| État | Description |
|---|---|
| `Banni` | Message rouge "You are banned from voting" |
| `Soumis` | Checkmark + hold-to-reveal pour voir le vote |
| `Confirmation` | Affiche la cible + Cancel (gris) / Confirm (rouge-brun) |
| `Normal` | Live tally + liste des cibles |

**Espace écran :** Carte `max-w-md`, liste scrollable `max-h-64`.

---

### 4.6 `livewire/shared/player-list.blade.php` — Liste joueurs

**Chemin :** `resources/views/livewire/shared/player-list.blade.php`
**Livewire :** `App\Livewire\Shared\PlayerList`
**Rôle :** Composant réutilisable affichant la liste des joueurs connectés avec point vert.

**Thème :**
- Polling : `wire:poll.3s`
- Ligne joueur : `bg-[#1A1510] rounded-lg border border-[#251E16] flex items-center gap-3 px-4 py-3`
- Point vert : `w-2 h-2 rounded-full bg-[#3A6B3A]`
- État vide : `text-[#9A8A6A] text-center py-8`

**États :**
- Peuplé : liste des joueurs
- Vide : message "No players yet"

**Espace écran :** Aucune taille intrinsèque — s'adapte au parent.

---

## 5. Pages narrateur

### 5.1 `livewire/narrator/narrator-lobby.blade.php` — Salon narrateur

**Chemin :** `resources/views/livewire/narrator/narrator-lobby.blade.php`
**Livewire :** `App\Livewire\Narrator\NarratorLobby`
**Route :** `GET /room/{room}/narrator` (nom : `lobby.narrator`)
**Rôle :** Configuration pré-partie par le narrateur : QR code, liste joueurs, configuration des rôles (+/-), validation et démarrage.

**Thème :**
- Polling : `wire:poll.3s`
- Code salon : `font-mono text-4xl tracking-[0.3em] text-[#C8922A] font-bold`
- Grille : `grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-4xl mx-auto`
- QR code : `bg-white p-4 rounded-lg` (fond blanc pour lisibilité)
- Ligne joueur : avec bouton Remove rouge `text-[#8B4040] bg-[#8B2020]/20`
- Configuration rôles : `grid grid-cols-2 md:grid-cols-3 gap-3`
  - Boutons +/- : `w-8 h-8 bg-[#251E16] rounded`
  - Compteur : `text-[#C8922A] font-mono w-6 text-center`
  - Bouton Start :
    - Enabled : `bg-[#3A6B3A] text-[#E8D9B5]`
    - Disabled : `bg-[#3A3530] text-[#6A6560] cursor-not-allowed`

**Composants utilisés :** Aucun sous-composant Livewire (tout est inline).

**États :**

| État | Description |
|---|---|
| Normal | QR + joueurs + config rôles |
| Validation errors | Bandeau rouge avec messages |
| Start enabled | Nombre de rôles = nombre de joueurs, validation passée |
| Start disabled | Bouton grisé, impossible de cliquer |
| Vide (aucun joueur) | Message "No players yet" |

**Espace écran :** Full-page scrollable. QR + joueurs prennent ~50% écran desktop, config rôles en dessous.

---

### 5.2 `livewire/narrator/narrator-dashboard.blade.php` — Tableau de bord narrateur

**Chemin :** `resources/views/livewire/narrator/narrator-dashboard.blade.php`
**Livewire :** `App\Livewire\Narrator\NarratorDashboard`
**Route :** `GET /game/{room}/narrator` (nom : `game.narrator`)
**Rôle :** Vue la plus complexe du projet (393 lignes). Contrôle central du jeu : changements de phase, actions en direct, votes, logs, messagerie, kick, et écran de fin.

**Thème :**
- Full-screen : `min-h-screen p-6` avec `max-w-7xl mx-auto`
- Header flex : round, phase, code salon, joueurs vivants, actions en attente

**Structure :** Grille responsives `grid-cols-1 lg:grid-cols-5`

| Colonne | Contenu |
|---|---|
| Gauche (3/5) | Grille joueurs `grid-cols-2 md:grid-cols-3 gap-3` |
| Droite (2/5) | Tally votes + Feed actions + Relations + Log + Historique |

**Cartes joueurs :**
- Fond : `bg-[#1A1510] border border-[#251E16] rounded-xl p-3`
- Mort : `opacity-40 border-[#8B2020]/30` + pseudo `line-through`
- Amoureux : `border-pink-900/40`
- Envoûté : `border-green-900/40`
- Badges : Dead (rouge), Banned (orange), Lover (rose), Enchanted (vert)
- Actions : Message (bleu), Kick (rouge)

**Contrôles de phase :** Boutons colorés :
- Night : `bg-[#1A3A5C] text-[#8AB8E8]` (bleu nuit)
- Day : `bg-[#5C4A1A] text-[#E8D89A]` (ambre)
- Voting : `bg-[#5C2A1A] text-[#E8A88A]` (rouge-brun)
- Finished : `bg-[#8B2020] text-[#E8B5B5]` (rouge foncé)
- Reveal lovers : `bg-pink-900/30 text-pink-400 border-pink-800/40`

**Panneaux de la colonne droite :**

1. **Vote tally** (phase voting seulement) : `max-h-48 overflow-y-auto`
2. **Night action feed** (phase night seulement) : `max-h-64 overflow-y-auto`
   - Chaque action : `bg-[#1A3A5C]/20 border-l-2 border-[#3A6A9A]`
   - Timestamp + type d'action avec placeholder traductible
3. **Relations** (si couple existe) : `max-h-48 overflow-y-auto`
   - Entrées : `border-l-2 border-pink-800/50`
4. **Game log** : `max-h-72 overflow-y-auto` — coloré par type :
   - `phase_changed` : `text-[#C8922A]`
   - `player_eliminated` : `text-[#8B2020]`
   - `night_resolved` : `text-[#8AB8E8]`
   - `vote_submitted` : `text-[#9A8A6A]`
   - `voting_resolved` : `text-[#E8A88A]`
   - `suspicious_access` : `text-[#E8B5B5]`
   - Etc.
5. **Action history** (toujours visible) : même style que log

**Overlays modaux :**

1. **Message input** (z-40) :
   - Fond `bg-black/60`, modal `max-w-md`
   - Textarea + boutons Cancel/Send

2. **Phase transition overlay** (z-50) : identique à la vue joueur

3. **Game over screen** (z-50) :
   - Fond `bg-black/80`, modal `max-w-lg`
   - Liste joueurs avec faction/rôle, vivants bordés d'or
   - Bouton "New Game" (narrateur seulement)

**États :**

| État | Description |
|---|---|
| Night | Phase buttons + action feed + player grid |
| Day | Phase buttons + game log + player grid (sans feed) |
| Voting | Phase buttons + vote tally + game log + player grid |
| Finished | Game over overlay + bouton New Game |
| No actions | Message italic "No actions yet" |
| No players | Message "No players" |
| No votes | Message "No votes yet" |
| Message modal ouvert | Overlay avec textarea |

**Espace écran :** Full-page scrollable. Desktop : grille 3/5 + 2/5. Mobile : empilement vertical.

---

## 6. Composants utilitaires

### 6.1 `components/placeholder-pattern.blade.php` — SVG Pattern

**Chemin :** `resources/views/components/placeholder-pattern.blade.php`
**Rôle :** Motif SVG décoratif (damier/crosshatch). Utilisé comme overlay visuel.
**Props :** `id` (auto-généré via `uniqid()`)

### 6.2 Erreurs — `errors/*`

**Layouts :**
- `errors/layout.blade.php` — Layout HTML basique (blanc, centré)
- `errors/minimal.blade.php` — Layout minimal avec normalize.css

**Pages d'erreur (toutes via `errors::minimal`) :**
401, 402, 403, 404, 419, 429, 500, 503

Chacune définit `title`, `code`, `message`.

**Thème :** Fond blanc, texte noir, centré — **complètement différent** du thème dark game. Ces pages sont déconnectées du design system principal car ce sont des erreurs système.

---

## 7. Mapping complet Route → Vue

| URI | Nom Route | Handler | Vue rendue |
|---|---|---|---|
| `/` | `home` | `Route::view` | `welcome.blade.php` |
| `/locale/{locale}` | `locale.switch` | Closure | Redirect |
| `/create` | `rooms.create` | `CreateRoom` Livewire | `livewire.lobby.create-room` |
| `/join/{code?}` | `rooms.join` | `JoinRoom` Livewire | `livewire.lobby.join-room` |
| `/room/{room}/narrator` | `lobby.narrator` | `NarratorLobby` Livewire | `livewire.narrator.narrator-lobby` |
| `/room/{room}/player` | `lobby.player` | `PlayerLobby` Livewire | `livewire.player.player-lobby` |
| `/game/{room}/narrator` | `game.narrator` | `NarratorDashboard` Livewire | `livewire.narrator.narrator-dashboard` |
| `/game/{room}/player` | `game.player` | `PlayerGameView` Livewire | `livewire.player.player-game-view` |
| `/api/rooms` | `api.rooms.create` | `LobbyController@create` | JSON |
| `/api/rooms/join` | `api.rooms.join` | `LobbyController@join` | JSON |
| `/api/vote` | `api.vote.submit` | `VoteController@submit` | JSON |

---

## 8. Tableau récapitulatif — Espace écran et comportement

| Vue | Layout | Centré ? | Max-Width | Scroll ? | Notes |
|---|---|---|---|---|---|
| `welcome` | Full-page | Oui | `max-w-sm` | Non | ~40% vertical |
| `create-room` | Full-page | Oui | `max-w-sm` | Non | ~30% vertical |
| `join-room` | Full-page | Oui | `max-w-sm` | Non | ~40% vertical |
| `player-lobby` | Full-page | Oui | `max-w-sm` | Rarement | Liste joueurs peut dépasser |
| `player-game-view` | Full-page | Oui | `max-w-md` (sous-composants) | Oui | Scroll si long contenu |
| `role-card` | Carte fixe | N/A | 256×384px | Non | Taille fixe |
| `night-action` | Carte | N/A | `max-w-md` | Interne | `max-h-64` scrollable |
| `voting-panel` | Carte | N/A | `max-w-md` | Interne | `max-h-64` scrollable |
| `player-list` | Liste | N/A | Auto | Non | Pas de taille intrinsèque |
| `narrator-lobby` | Full-page grille | Oui | `max-w-4xl` | Oui | 2 colonnes desktop |
| `narrator-dashboard` | Full-page grille | Oui | `max-w-7xl` | Oui | 5 colonnes desktop |
| Message modal | Overlay fixe | Oui | `max-w-md` | Non | z-40, fond black/60 |
| Game over overlay | Overlay fixe | Oui | `max-w-lg` | Interne | `max-h-64` scrollable |
| Phase overlay | Overlay fixe | Oui | N/A (full) | Non | z-50, auto-dismiss 1.5s |
| Result notification | Top-center fixe | Oui | `max-w-md` | Non | Auto-dismiss 6s |

---

## 9. Interactivité et comportements communs

### Motif "Hold to Reveal"
Utilisé sur : role-card, résultats Seer/Fox, info amoureux, action nocturne soumise, vote soumis.
Pattern Alpine.js :
```alpine
x-data="{ revealed: false }"
x-on:mousedown="revealed = true"
x-on:mouseup="revealed = false"
x-on:mouseleave="revealed = false"
x-on:touchstart="revealed = true"
x-on:touchend="revealed = false"
```

### Confirmation en deux étapes
Toute action critique (vote, action de nuit, transition de phase, kick, nouveau jeu) suit :
1. Sélection → 2. Confirmation (avec bouton Cancel/Confirm)

### Transitions de phase
Overlay plein écran avec animation, utilisé côté joueur et narrateur.
Durée : 700ms entrée / 500ms sortie / 1500ms total.

### Notifications auto-dissoutes
- Résultats personnels : 6 secondes
- Éliminations nocturnes : 8 secondes

### Real-time
- Polling Livewire : 3s (player-list, narrator-lobby), 10s (player-game-view)
- WebSocket Echo : phases, éliminations, actions, résultats privés

---

## 10. Langues (i18n)

Tous les textes utilisateur passent par les fichiers `lang/` :
- `en/` et `fr/` — 6 fichiers chacun (ui.php, roles.php, narration.php, lobby.php, game.php, decoys.php)
- Parfaitement symétriques (mêmes clés traduites)
- Toggle de locale uniquement sur la page d'accueil

---

## 11. Arborescence complète des vues

```
resources/views/
├── components/
│   └── placeholder-pattern.blade.php    (SVG décoratif)
├── errors/
│   ├── layout.blade.php                  (layout blanc minimal)
│   ├── minimal.blade.php                 (layout inline CSS)
│   └── 401, 402, 403, 404, 419, 429, 500, 503.blade.php
├── layouts/
│   └── app.blade.php                     (coque HTML principale)
├── livewire/
│   ├── lobby/
│   │   ├── create-room.blade.php         (créer un salon)
│   │   └── join-room.blade.php           (rejoindre un salon)
│   ├── narrator/
│   │   ├── narrator-lobby.blade.php      (salon narrateur pré-partie)
│   │   └── narrator-dashboard.blade.php  (tableau de bord en jeu)
│   ├── player/
│   │   ├── night-action.blade.php        (panneau action de nuit)
│   │   ├── player-game-view.blade.php    (vue de jeu joueur)
│   │   ├── player-lobby.blade.php        (salon d'attente joueur)
│   │   ├── role-card.blade.php           (carte rôle)
│   │   └── voting-panel.blade.php        (panneau de vote)
│   └── shared/
│       └── player-list.blade.php         (liste joueurs réutilisable)
├── partials/
│   ├── head.blade.php                    (partiel head — non utilisé)
│   └── settings-heading.blade.php        (partiel settings — non utilisé)
└── welcome.blade.php                     (page d'accueil)
```
