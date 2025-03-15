#!/usr/bin/env bash
#++kubectl create namespace oreplay
# kubectl delete namespace oreplay
#++kubectl -n oreplay create secret generic sec-edu-back --dry-run=client \
#++  --from-env-file="./oreplay/.env.nginx-oreplay.env" \
#++  --output json | kubeseal | tee ./oreplay/oreplay-secret-oreplay.yaml
#++kubectl apply -f ./oreplay/oreplay-secret-oreplay.yaml -n oreplay
#kubectl delete -f oreplay/oreplay-secret-oreplay.yaml -n oreplay
#++helm install mysql-db bitnami/mysql -n oreplay \
#++  --set auth.rootPassword=****** \
#++  --set mysqlUser=oreplayadmin \
#++  --set mysqlPassword=****** \
#++  --set mysqlDatabase=app_rest
#mysql -h mysql-db.oreplay.svc.cluster.local -uroot -p
helm upgrade -i api-memcached bitnami/memcached -n oreplay
# helm uninstall api-memcached -n oreplay
### Before installing ssl-ingress, DNS must be properly set
helm upgrade -i ssl-ingress ./subssl -n oreplay --set ingress.host=www.oreplay.es
# helm uninstall ssl-ingress -n oreplay
helm upgrade -i ct-frontend ./ct-frontend -n oreplay --set image.tag=v0.2.17 \
  --set replicaCount=1 \
  --set frontendService.targetPort=4173 \
  --set image.repository=oreplay/frontend
# helm uninstall ct-frontend -n oreplay
helm upgrade -i cakeapi ./nginx -n oreplay --set image.tag=0.2.18 \
  --set replicaCount=1 \
  --set container.envTimezone=UTC \
  --set image.repository=oreplay/backend
# helm uninstall cakeapi -n oreplay
