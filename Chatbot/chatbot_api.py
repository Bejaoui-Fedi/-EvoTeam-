import urllib.request
import json
import re
import os
import time
import importlib
from typing import List, Dict, Optional

def charger_env(chemin: str):
    """Charge les variables d'environnement depuis un fichier .env manuellement."""
    if not os.path.exists(chemin):
        return
    with open(chemin, "r", encoding="utf-8") as f:
        for ligne in f:
            ligne = ligne.strip()
            if not ligne or ligne.startswith("#"):
                continue
            if "=" in ligne:
                cle, valeur = ligne.split("=", 1)
                os.environ[cle.strip()] = valeur.strip()

# Charger les variables depuis le dossier où se trouve le script (Chatbot/.env)
script_dir = os.path.dirname(os.path.abspath(__file__))
env_path = os.path.join(script_dir, ".env")
charger_env(env_path)


# ==========================================
# CONFIGURATION - RÉCUPÉRATION DES CLÉS
# ==========================================
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
GROQ_API_KEY = os.getenv("GROQ_API_KEY")

# ==========================================
# PERSONA EVOLIA - CONTEXTE PSYCHOLOGIQUE
# ==========================================
SYSTEM_PROMPT = """
Tu es l'assistant IA officiel de la plateforme **Evolia**, spécialisée dans la psychologie et le développement personnel.
Ton rôle est d'accompagner les utilisateurs avec empathie, professionnalisme et bienveillance.
Tes domaines d'expertise sont : la gestion du stress, la confiance en soi, la motivation, les relations humaines et le bien-être mental.
Règles de conduite :
1. Réponds toujours de manière constructive et encourageante.
2. Si une question sort totalement du cadre du bien-être, essaie d'y répondre brièvement tout en ramenant subtilement le sujet vers le développement personnel ou la perspective mentale.
3. Ne remplace jamais un vrai psychologue en cas de crise grave, mais suggère des pistes de réflexion.
"""

class ChatbotAI:
    def __init__(self):
        self.nom_utilisateur = ""
        self.mode_ai = "gemini" # 'gemini' ou 'groq'
        self.mode_vocal = True
        
        # Initialisation du moteur de synthèse vocale (TTS)
        try:
            # Importation dynamique pour éviter les erreurs d'affichage dans VS Code
            pyttsx3 = importlib.import_module('pyttsx3')
            self.engine = pyttsx3.init()
            # Configuration de la voix française
            vocal_voices = self.engine.getProperty('voices')
            for voice in vocal_voices:
                if "FRA" in voice.id.upper() or "FRENCH" in voice.name.upper():
                    self.engine.setProperty('voice', voice.id)
                    break
        except Exception as e:
            # On ne print rien ici pour éviter de polluer si pas de vocal
            self.engine = None

        print("Initialisation du Chatbot Evolia AI...")
        print(f"Modes disponibles : Gemini, Groq")
        print(f"Option vocale : Disponible")

    def parler(self, texte: str):
        """Lit le texte à haute voix si le mode vocal est actif."""
        if self.mode_vocal and self.engine:
            # Nettoyer les caractères spéciaux markdown pour une meilleure lecture
            texte_propre = re.sub(r'[*_#]', '', texte)
            self.engine.say(texte_propre)
            self.engine.runAndWait()

    def ecouter(self) -> Optional[str]:
        """Écoute le microphone et convertit la voix en texte."""
        try:
            # Importation dynamique pour éviter les erreurs d'affichage dans VS Code
            sr = importlib.import_module('speech_recognition')
            r = sr.Recognizer()
            with sr.Microphone() as source:
                print("\n[ÉCOUTE EN COURS...] Parlez maintenant...")
                r.adjust_for_ambient_noise(source, duration=0.5)
                audio = r.listen(source, timeout=10, phrase_time_limit=15)
            
            print("[TRAITEMENT VOCAL...]")
            texte = r.recognize_google(audio, language="fr-FR")
            print(f"Vous avez dit : {texte}")
            return texte
        except Exception as e:
            print(f"[INFO Voice] Problème micro ou détection : {e}")
            return None

    def appeler_gemini(self, message: str) -> str:
        if GEMINI_API_KEY == "VOTRE_CLE_GEMINI_ICI" or not GEMINI_API_KEY:
            raise ValueError("Clé API Gemini manquante.")
            
        # Modèle mis à jour vers gemini-2.5-flash
        url = f"https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={GEMINI_API_KEY}"
        
        # Pour Gemini, on combine le message système avec le message utilisateur
        prompt_complet = f"{SYSTEM_PROMPT}\n\nUtilisateur: {message}"
        
        data = {
            "contents": [{
                "parts": [{"text": prompt_complet}]
            }]
        }
        
        try:
            req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'))
            req.add_header('Content-Type', 'application/json')
            
            with urllib.request.urlopen(req, timeout=15) as response:
                res_data = json.loads(response.read().decode('utf-8'))
                return res_data['candidates'][0]['content']['parts'][0]['text']
        except Exception as e:
            raise RuntimeError(f"Gemini API Error: {str(e)}")

    def appeler_groq(self, message: str) -> str:
        if GROQ_API_KEY == "VOTRE_CLE_GROQ_ICI" or not GROQ_API_KEY:
            raise ValueError("Clé API Groq manquante.")
            
        url = "https://api.groq.com/openai/v1/chat/completions"
        data = {
            "model": "llama-3.3-70b-versatile",
            "messages": [
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": message}
            ]
        }
        
        try:
            req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'))
            req.add_header('Content-Type', 'application/json')
            req.add_header('Authorization', f'Bearer {GROQ_API_KEY}')
            
            with urllib.request.urlopen(req, timeout=15) as response:
                res_data = json.loads(response.read().decode('utf-8'))
                return res_data['choices'][0]['message']['content']
        except Exception as e:
            raise RuntimeError(f"Groq API Error: {str(e)}")

    def obtenir_reponse(self, message: str) -> str:
        """Tente d'obtenir une réponse de l'IA choisie, avec fallback automatique."""
        primary = self.mode_ai
        secondary = "groq" if primary == "gemini" else "gemini"
        
        try:
            # Premier essai
            if primary == "gemini":
                return self.appeler_gemini(message)
            else:
                return self.appeler_groq(message)
        except Exception as e1:
            print(f"\n[INFO] {primary.upper()} indisponible ({e1}). Tentative avec {secondary.upper()}...")
            try:
                # Deuxième essai (Fallback)
                if secondary == "gemini":
                    return self.appeler_gemini(message)
                else:
                    return self.appeler_groq(message)
            except Exception as e2:
                return f"[ERREUR CRITIQUE] Les deux IA sont indisponibles.\nGemini: {e1}\nGroq: {e2}"

    def demarrer(self):
        print("\n" + "=" * 60)
        print("        EVOLIA AI : PSYCHOLOGIE & DÉVELOPPEMENT")
        print("=" * 60 + "\n")
        
        self.nom_utilisateur = input("Votre prénom : ")
        
        print(f"\nBonjour {self.nom_utilisateur} !")
        print(f"Mode actuel : {self.mode_ai.upper()}")
        print(f"Vocal : {'ACTIF' if self.mode_vocal else 'DÉSACTIVÉ'}")
        print("Commandes spéciales : vocal, gemini, groq, quitter\n")
        
        actif = True
        while actif:
            try:
                # Priorité au vocal si actif
                if self.mode_vocal:
                    user_input = self.ecouter()
                    if not user_input:
                        print("(Pas d'entrée vocale détectée, bascule temporaire sur le clavier...)")
                        user_input = input(f"{self.nom_utilisateur} > ").strip()
                else:
                    user_input = input(f"{self.nom_utilisateur} > ").strip()
            except (EOFError, KeyboardInterrupt):
                break
                
            if not user_input:
                continue
                
            cmd = user_input.lower()
            if cmd in ["quitter", "exit", "au revoir"]:
                msg = f"\nAu revoir {self.nom_utilisateur} ! Prends soin de toi."
                print(msg)
                if self.mode_vocal: self.parler(msg)
                actif = False
            elif cmd == "vocal":
                self.mode_vocal = not self.mode_vocal
                status = "ACTIF" if self.mode_vocal else "DÉSACTIVÉ"
                print(f">>> Mode vocal : {status}")
                if self.mode_vocal: self.parler("Mode vocal activé. Je vous écoute.")
            elif cmd == "gemini":
                self.mode_ai = "gemini"
                print(">>> Mode changé vers GEMINI")
            elif cmd == "groq" or cmd == "grok":
                self.mode_ai = "groq"
                print(">>> Mode changé vers GROQ")
            else:
                print("Chatbot (IA en cours...)")
                reponse = self.obtenir_reponse(user_input)
                print(f"Chatbot > {reponse}\n")
                if self.mode_vocal:
                    self.parler(reponse)

if __name__ == "__main__":
    try:
        ChatbotAI().demarrer()
    except Exception as e:
        print(f"Erreur : {e}")
