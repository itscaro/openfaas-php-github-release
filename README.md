### Function to get the latest release on GitHub and URL to the asset

*To be used with OpenFaaS*

#### Usage
Use header `Content-Type: application/json` or header `binary/octet-stream` to download the asset 

Request

```json
{
    "repo": "openfaas/faas-cli"
}
```

Response

```json
{
    "version": "1.2.3"
}
```

Request

```json
{
    "repo": "openfaas/faas-cli",
    "arch": "linux"
}
```

Response

```json
{
    "version": "1.2.3",
    "name":"faas-cli",
    "url":"https://github.com/openfaas/faas-cli/releases/download/0.5.2/faas-cli"
}
```
