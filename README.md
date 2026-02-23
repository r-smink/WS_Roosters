# RoosterPlanner Pro

Compleet roosterplanningssysteem voor WordPress met admin portal en mobile web app voor medewerkers.

## Functionaliteiten

### Admin Portal
- 📅 **Roosters plannen** - Maak en bewerk roosters per locatie
- 👥 **Medewerkers beheren** - Voeg medewerkers toe en wijs locaties toe
- 📍 **Locaties & Shifts** - Beheer meerdere locaties en shift types
- ✅ **Beschikbaarheid** - Bekijk welke medewerkers wanneer beschikbaar zijn
- 🔄 **Ruilingen & Verlof** - Beheer ruilverzoeken en verlofaanvragen
- 💬 **Chat & Berichten** - Communiceer met het team via in-app chat
- 📊 **Rapportages** - Bekijk statistieken en exporteer data

### Medewerker App (Mobile Web)
- 📅 **Roosters bekijken** - Persoonlijk en algemeen rooster inzien
- ✅ **Beschikbaarheid doorgeven** - Geef eenvoudig beschikbaarheid door per maand
- 🔄 **Shifts ruilen** - Ruil diensten met collega's
- 🤒 **Ziekmelden** - Meld je ziek met automatische vervangingsvraag
- 💬 **Team Chat** - Chat met collega's en ontvang mededelingen
- 👤 **Profiel** - Bekijk je statistieken en exporteer rooster

## Installatie

1. **Upload de plugin**
   - Upload de `rooster-planner-pro` map naar `/wp-content/plugins/`
   - Of installeer via WordPress Admin → Plugins → Nieuwe plugin

2. **Activeer de plugin**
   - Ga naar WordPress Admin → Plugins
   - Activeer "RoosterPlanner Pro"

3. **Configureer pagina's**
   - Maak pagina's aan voor elke shortcode (zie onder)
   - Voeg de shortcodes toe aan de pagina's

4. **Stel de startpagina in**
   - Configureer een custom login redirect naar `/medewerker-dashboard/`

## Shortcodes

Plaats deze shortcodes op WordPress pagina's:

| Shortcode | Functie | Voorbeeld URL |
|-----------|---------|---------------|
| `[roosterplanner_login]` | Medewerker login | `/medewerker-login/` |
| `[roosterplanner_dashboard]` | Dashboard | `/medewerker-dashboard/` |
| `[roosterplanner_rooster]` | Rooster bekijken | `/medewerker-rooster/` |
| `[roosterplanner_beschikbaarheid]` | Beschikbaarheid doorgeven | `/medewerker-beschikbaarheid/` |
| `[roosterplanner_ruilenen]` | Shifts ruilen | `/medewerker-ruilen/` |
| `[roosterplanner_chat]` | Team chat | `/medewerker-chat/` |
| `[roosterplanner_ziekmelden]` | Ziekmelding | `/medewerker-ziekmelden/` |
| `[roosterplanner_profielformulier]` | Profiel pagina | `/medewerker-profiel/` |

## Eerste Setup

1. **Maak locaties aan**
   - Ga naar Admin → Rooster Planner → Locaties & Shifts
   - Voeg je locaties toe (bv. "Serva", "Isselt")

2. **Configureer shifts**
   - Voeg shift types toe per locatie
   - Voorbeeld: Kassa openen (05:50-12:00), Tussen dienst (10:00-16:00)

3. **Voeg medewerkers toe**
   - Ga naar Admin → Rooster Planner → Medewerkers
   - Medewerkers moeten eerst een WordPress account hebben

4. **Plan het eerste rooster**
   - Ga naar Admin → Rooster Planner → Roosters Plannen
   - Klik op een dag om een dienst toe te voegen

## Database Tabellen

De plugin maakt automatisch de volgende tabellen aan:

- `{prefix}rp_locations` - Locaties
- `{prefix}rp_shifts` - Shift definities
- `{prefix}rp_employees` - Medewerker profielen
- `{prefix}rp_employee_locations` - Medewerker-locatie koppeling
- `{prefix}rp_schedules` - Geplande diensten
- `{prefix}rp_availability` - Beschikbaarheidsaangifte
- `{prefix}rp_shift_swaps` - Ruilverzoeken
- `{prefix}rp_timeoff` - Verlofverzoeken
- `{prefix}rp_chat_messages` - Chat berichten
- `{prefix}rp_notifications` - Notificaties
- `{prefix}rp_fixed_schedules` - Vaste rooster templates

## Notificaties

Het systeem verstuurt automatisch notificaties voor:
- 🔔 Dagelijks reminder voor komende diensten
- 📅 Maandelijkse reminder voor beschikbaarheid deadline (14e)
- 🔄 Nieuwe ruilverzoeken
- ✅ Goedgekeurde/afgewezen verzoeken
- 💬 Nieuwe chat berichten
- 🤒 Ziekmeldingen (met vervangingsvraag)

## Mobile App (PWA)

De plugin werkt als een Progressive Web App:
1. Open de medewerker login op je telefoon
2. Voeg toe aan homescreen
3. Werkt offline voor bekeken data
4. Push notificaties (indien ondersteund)

## Veelgestelde Vragen

**Q: Kan ik bestaande gebruikers importeren?**  
A: Ja, maak eerst WordPress gebruikers aan, dan kun je ze toevoegen als medewerker.

**Q: Kan ik roosters kopiëren naar volgende maand?**  
A: Ja, gebruik de "Dupliceer vorige maand" knop in het rooster overzicht.

**Q: Kan een medewerker op meerdere locaties werken?**  
A: Ja, wijs meerdere locaties toe bij het bewerken van een medewerker.

**Q: Hoe werkt het ziekmelden?**  
A: Medewerker meldt zich ziek → Dienst wordt geannuleerd → Alle collega's krijgen notificatie voor vervanging.

## Technische Details

- **Versie:** 1.0.0
- **WordPress:** 5.8+
- **PHP:** 7.4+
- **MySQL:** 5.7+
- **Taal:** Nederlands
- **Tijdzone:** Amsterdam (Europe/Amsterdam)

## Support

Voor vragen of ondersteuning, neem contact op met je systeembeheerder.

## Changelog

### 1.0.0
- Eerste release
- Complete roosterplanning functionaliteit
- Admin dashboard
- Medewerker mobile web app
- Chat systeem
- Notificatiesysteem
