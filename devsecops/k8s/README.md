# Deploy pre
    Prev-step -> change version variable vale on values-pre.yaml
    $ helm install/upgrade security-scan-ci . -f ./values-pre.yaml -n devsecops
 
# Status
    $ kubectl get pods -o wide -n devsecops

# Debug
    $ kubectl describe pod <POD_NAME> -n devsecops'
