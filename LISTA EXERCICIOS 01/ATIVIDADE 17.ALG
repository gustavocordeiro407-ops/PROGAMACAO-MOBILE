algoritmo "ATIVIDADE 17"
var user, pass: caractere
inicio
   escreva("Usuário: ")
   leia(user)
   escreva("Senha: ")
   leia(pass)
   se (user = "admin") e (pass = "1234") entao
      escreva("Acesso permitido")
   senao
      escreva("Acesso negado")
   fimse
fimalgoritmo