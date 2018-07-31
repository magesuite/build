pipeline {
    agent any;
    
    parameters {
        
        string(name: 'ARTIFACT_REPO', defaultValue: params.ARTIFACT_REPO, description: 'Artifact git repo URL')
        string(name: 'ARTIFACT_BRANCH', defaultValue: params.ARTIFACT_BRANCH ?: 'master', description: 'Artifact git repo URL')
        string(name: 'CREATIVESHOP_REPO', defaultValue: params.CREATIVESHOP_REPO ?: 'git@gitlab.creativestyle.pl:m2c/m2c.git', description: 'Project repo URL')
        string(name: 'CREATIVESHOP_BRANCH', defaultValue: params.CREATIVESHOP_BRANCH, description: 'Project repo branch')
        credentials(name: 'GIT_CREDS', defaultValue: params.GIT_CREDS ?: '1aa37c8c-73f1-4b3c-a2e5-149de20b989c', description: 'Git repo access credentials')
    }
    
    stages {
        stage('Clone current artifacts') {
            steps {
                checkout([
                    $class: 'GitSCM',
                    branches: [[name: "*/${params.ARTIFACT_BRANCH}"]],
                    userRemoteConfigs: [[url: params.ARTIFACT_REPO, credentialsId: params.GIT_CREDS]]
                ])
            }
        }
        
        stage('Install current project configuration') {
            steps {
                dir('creativeshop-project') {
                    checkout([
                        $class: 'GitSCM',
                        branches: [[name: "*/${params.CREATIVESHOP_BRANCH}"]],
                        userRemoteConfigs: [[url: params.CREATIVESHOP_REPO, credentialsId: params.GIT_CREDS]]
                    ])
                    
                    fileOperations([fileCopyOperation(excludes: '.git,composer.lock', flattenFiles: false, includes: '*,.*', targetLocation: "${WORKSPACE}")])
                }
            }
        }
    
        stage('Decrypt composer auth') {
            steps {
                script {
                    sh 'env'
                    sh 'ansible-vault --vault-password-file=~/.raccoon-vault-password --output=auth.json decrypt auth.json.encrypted'
                }
            } 
            when { expression { return fileExists('auth.json.encrypted') && !fileExists('auth.json') } }
        }
        
        stage('Install composer deps') {
            steps {
                script {
                    sh 'php /usr/local/bin/composer update'
                }
            } 
            when { expression { return !fileExists('vendor') } }
        }
        
        stage('Push artifacts') {
            steps {
                script {
                    sshagent (credentials: [params.GIT_CREDS]) {
                        writeFile file: '.git/info/exclude', text: 'creativeshop-project\n'
                        sh 'git add . -A'
                        sh 'git commit -m "${env.BUILD_NUMBER}"'
                        sh 'git push'
                    }
                }
            }
        }
    }
}
