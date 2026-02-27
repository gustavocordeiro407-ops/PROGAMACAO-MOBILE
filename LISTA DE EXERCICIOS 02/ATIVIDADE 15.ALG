algoritmo "ATIVIDADE 15"
var
   n, i, a, b, prox, pares: inteiro
inicio
   escreva("Quantos termos? ")
   leia(n)

   a <- 0 // Primeiro termo
   b <- 1 // Segundo termo
   pares <- 1 // O zero é par

   escreva(a, " ", b, " ")

   para i de 3 ate n faca
      prox <- a + b
      escreva(prox, " ")

      se prox % 2 = 0 entao
         pares <- pares + 1
      fimse

      a <- b
      b <- prox
   fimpara
   escreval("")
   escreval("Qtd Pares: ", pares)
fimalgoritmo