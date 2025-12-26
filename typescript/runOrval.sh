#!/bin/bash

#curl -s 'http://host.docker.internal/api/v3/openapi/json?no_description=1' --output v1api.json
#tail -f /dev/null
#rm -R /mnt/typescript/domain
#rm -R /mnt/typescript/infrastructure/repositories
npm install
npm run openapi:download
rm /mnt/typescript/domain/types/v1api/index.ts
npm run orval-build
chmod 777 -R /mnt/typescript
echo "Finished at $(date '+%Y-%m-%d %H:%M:%S')"
