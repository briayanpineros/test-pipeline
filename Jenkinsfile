
pipeline {
    agent any

    environment {
            VERSION = ''
    }

    stages {
        stage('Checkout') {
            steps {
                checkout([$class: 'GitSCM', branches: [[name: 'origin/main']], extensions: [], userRemoteConfigs: [[url: 'https://github.com/briayanpineros/test-pipeline.git']]])
            }
        }

        stage('Initialize the variables') {
            steps {
                script {
                    propiedades = readYaml(file: 'propiedades.yml')
                    VERSION = propiedades.version
                }
            }
        }

        stage('Docker Image Build') {
            steps {
                sh 'docker build -t public.ecr.aws/h1y1q1x5/drupal-php:' + VERSION + ' .'
            }
        }
        stage('Push Docker Image to ECR') {
            steps {
                withAWS(credentials: 'conil-preproduccion-credential', region: 'eu-west-3') {
                    sh 'aws ecr-public get-login-password --region us-east-1 | docker login --username AWS --password-stdin public.ecr.aws/h1y1q1x5'
                    sh 'docker push public.ecr.aws/h1y1q1x5/drupal-php:' + VERSION
                }
            }
        }

        stage('Deploy PRE') {
            input {
                message '¿Quieres desplegar en PRE?'
                ok 'Si'
            }

            steps {
                echo 'Tag imagen antes'
                sh 'cat orquestador/pre/deployment-pre.yml | grep image: && rm -f orquestador/pre/deployment-pre-modificada.yml'

                script {
                    deployment = readYaml(file: 'orquestador/pre/deployment-pre.yml')
                    echo deployment.toString()
                    deployment.spec.template.spec.containers[0].image = 'public.ecr.aws/h1y1q1x5/drupal-php:' + VERSION
                    writeYaml file: 'orquestador/pre/deployment-pre-modificada.yml', data: deployment
                }

                echo 'Tag imagen despues'
                sh 'cat orquestador/pre/deployment-pre-modificada.yml | grep image:'

                withAWS(credentials: 'conil-preproduccion-credential', region: 'eu-west-3') {
                    sh 'aws eks update-kubeconfig --region eu-west-3 --name conil-preproduccion'
                    sh 'kubectl apply -f orquestador/pre/deployment-pre-modificada.yml'
                }
            }
        }

        stage('Deploy PRO') {
            input {
                message '¿Quieres desplegar en PRO?'
                ok 'Si'
            }

            steps {
                echo 'Tag imagen antes'
                sh 'cat orquestador/pro/deployment-pro.yml | grep image: && rm -f orquestador/pro/deployment-pro-modificada.yml'

                script {
                    deployment = readYaml(file: 'orquestador/pro/deployment-pro.yml')
                    echo deployment.toString()
                    deployment.spec.template.spec.containers[0].image = 'public.ecr.aws/h1y1q1x5/drupal-php:' + VERSION
                    writeYaml file: 'orquestador/pro/deployment-pro-modificada.yml', data: deployment
                }

                echo 'Tag imagen despues'
                sh 'cat orquestador/pro/deployment-pro-modificada.yml | grep image:'

                withAWS(credentials: 'conil-produccion-credential', region: 'eu-west-3') {
                    sh 'aws eks update-kubeconfig --region eu-west-3 --name conil-produccion'
                    sh 'kubectl apply -f orquestador/pro/deployment-pro-modificada.yml'
                }
            }
        }
    }
}