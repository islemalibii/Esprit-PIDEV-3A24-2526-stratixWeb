import json

def analyser_et_suggerer(stocks_actuels, produits_config):
    suggestions = []

    for produit in produits_config:
        # Vérifier si on peut créer le produit avec les ressources en stock
        peut_creer = True
        max_possible = float('inf')
        cout_total = 0

        for composant, quantite_requise in produit['composants'].items():
            stock_dispo = stocks_actuels.get(composant, 0)
            if stock_dispo < quantite_requise:
                peut_creer = False
                break
            
            # Calculer combien d'unités on peut faire au max avec ce composant
            max_possible = min(max_possible, stock_dispo // quantite_requise)
            cout_total += quantite_requise * stocks_actuels.get(f"{composant}_prix", 0)

        if peut_creer and max_possible > 0:
            marge = produit['prix_vente'] - cout_total
            score_ia = (marge * 0.6) + (max_possible * 0.4) # Pondération IA
            
            suggestions.append({
                'nom_produit': produit['nom'],
                'quantite_suggeree': int(max_possible),
                'marge_unitaire': round(marge, 2),
                'score_opportunite': round(score_ia, 1)
            })

    # Trier par le meilleur score d'opportunité
    return sorted(suggestions, key=lambda x: x['score_opportunite'], reverse=True)

# Exemple de données venant de ta base Symfony
stocks = {"Cisco_Port": 50, "Cisco_Port_prix": 10, "Cable_Cat6": 100, "Cable_Cat6_prix": 2}
configurations = [
    {"nom": "Kit Réseau Standard", "prix_vente": 150, "composants": {"Cisco_Port": 8, "Cable_Cat6": 10}}
]

print(json.dumps(analyser_et_suggerer(stocks, configurations)))