algoritmo "ATIVIDADE 09"
var
   n, i, divisores: inteiro
inicio
   escreva("Digite um número > 1: ")
   leia(n)
   divisores <- 0

   para i de 1 ate n faca
      se n % i = 0 entao
         divisores <- divisores + 1
      fimse
   fimpara

   se divisores = 2 entao
      escreva("É Primo")
   senao
      escreva("Não é Primo")
   fimse
fimalgoritmo