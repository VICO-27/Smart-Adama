import re

with open('/home/vico/Projects/Dev/Smart Adama/frontend/src/router/index.ts', 'r') as f:
    content = f.read()

# Replace "import X from '@/views/Y'" with "const X = () => import('@/views/Y')"
# Except LandingView
def replace_import(match):
    name = match.group(1)
    path = match.group(2)
    if name in ['LandingView', 'OAuthCallbackView']:
        return match.group(0)
    return f"const {name} = () => import('{path}')"

content = re.sub(r"import\s+(\w+)\s+from\s+'(@/views/[^']+)'", replace_import, content)

# But wait, AdminDocumentManager was already replaced to "const AdminDocumentManager = () => import(...)"
# It won't match the regex because it's not "import ... from" anymore, so that's fine.

with open('/home/vico/Projects/Dev/Smart Adama/frontend/src/router/index.ts', 'w') as f:
    f.write(content)
