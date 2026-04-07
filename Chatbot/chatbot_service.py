import sys
import os
import json
import urllib.request
import re

# Forcer l'encodage UTF-8 pour la sortie standard sur Windows
if sys.platform == "win32":
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def charger_env(chemin: str):
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

# Charger les variables depuis le dossier où se trouve le script
script_dir = os.path.dirname(os.path.abspath(__file__))
env_path = os.path.join(script_dir, ".env")
charger_env(env_path)


GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
GROQ_API_KEY = os.getenv("GROQ_API_KEY")


SYSTEM_PROMPT = """
Tu es l'assistant IA officiel de la plateforme **Evolia**, spécialisée dans la psychologie et le développement personnel.
Ton rôle est d'accompagner les utilisateurs avec empathie, professionnalisme et bienveillance.
Tes domaines d'expertise sont : la gestion du stress, la confiance en soi, la motivation, les relations humaines et le bien-être mental.
"""

def appeler_gemini(message: str, context: dict = None) -> str:
    if not GEMINI_API_KEY:
        raise ValueError("Clé API Gemini manquante.")
        
    url = f"https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={GEMINI_API_KEY}"
    
    context_str = ""
    if context:
        name = context.get('name', 'Utilisateur')
        role = context.get('role', 'Patient')
        context_str = f"Tu parles à {name}, dont le rôle sur la plateforme est {role}.\n"
        
    prompt_complet = f"{SYSTEM_PROMPT}\n{context_str}\nUtilisateur: {message}"
    
    data = {
        "contents": [{
            "parts": [{"text": prompt_complet}]
        }]
    }
    
    req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'))
    req.add_header('Content-Type', 'application/json')
    
    with urllib.request.urlopen(req, timeout=15) as response:
        res_data = json.loads(response.read().decode('utf-8'))
        return res_data['candidates'][0]['content']['parts'][0]['text']


def appeler_groq(message: str, context: dict = None) -> str:
    if not GROQ_API_KEY:
        raise ValueError("Clé API Groq manquante.")
    
    context_str = ""
    if context:
        name = context.get('name', 'Utilisateur')
        role = context.get('role', 'Patient')
        context_str = f"Tu parles à {name}, dont le rôle sur la plateforme est {role}.\n"
        
    system_with_context = f"{SYSTEM_PROMPT}\n{context_str}"
    
    url = "https://api.groq.com/openai/v1/chat/completions"
    data = {
        "model": "llama-3.3-70b-versatile",
        "messages": [
            {"role": "system", "content": system_with_context},
            {"role": "user", "content": message}
        ]
    }
    
    req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'))
    req.add_header('Content-Type', 'application/json')
    req.add_header('Authorization', f'Bearer {GROQ_API_KEY}')
    
    with urllib.request.urlopen(req, timeout=15) as response:
        res_data = json.loads(response.read().decode('utf-8'))
        return res_data['choices'][0]['message']['content']


def obtenir_reponse(message: str, context: dict = None) -> str:
    """Essaie Gemini, puis Groq en fallback si Gemini est indisponible."""
    try:
        return appeler_gemini(message, context)
    except Exception as e1:
        try:
            return appeler_groq(message, context)
        except Exception as e2:
            return f"[ERREUR] Les deux IA sont indisponibles.\nGemini: {e1}\nGroq: {e2}"


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No message provided"}))
        sys.exit(1)
        
    user_message = sys.argv[1]
    
    # Extraire le contexte si présent (format: "message|name|role")
    context = {}
    if "|" in user_message:
        parts = user_message.split("|")
        user_message = parts[0]
        if len(parts) >= 2: context['name'] = parts[1]
        if len(parts) >= 3: context['role'] = parts[2]

    response_text = obtenir_reponse(user_message, context)

    print(json.dumps({"response": response_text}, ensure_ascii=False))

